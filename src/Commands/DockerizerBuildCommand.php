<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use SvenVanderwegen\Dockerizer\Services\MySQLDockerService;
use SvenVanderwegen\Dockerizer\Services\RedisDockerService;
use Symfony\Component\Yaml\Yaml;

final class DockerizerBuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dockerizer:build {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the docker configuration for your application.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🐳 Dockerizer: Generating Docker configuration files...');

        // Check if config exists
        if (! Config::has('dockerizer')) {
            $this->error('Config file "dockerizer.php" is missing or not loaded.');

            return Command::FAILURE;
        }

        // Check if composer.json exists
        if (! File::exists(base_path('composer.json'))) {
            $this->error('composer.json not found in project root.');

            return Command::FAILURE;
        }

        // Read settings from config
        $configDirectory = Config::get('dockerizer.config_directory', '.dockerizer');
        $force = $this->option('force');

        // Create directory structure
        $this->createDirectories([
            base_path($configDirectory),
            base_path('.github/workflows'),
        ]);

        // Get the current Git branch (default to main if not found)
        $gitBranch = $this->getDefaultGitBranch();

        // Detect PHP extensions from composer.json
        $phpExtensions = $this->detectPhpExtensions();

        // Generate all required files
        $this->generateDockerComposeFile($configDirectory, $force);
        $this->generateDockerfile($configDirectory, $phpExtensions, $force);
        $this->generateNginxDockerfile($configDirectory, $force);
        $this->generateEntrypointScript($configDirectory, $force);
        $this->generateNginxConfig($configDirectory, $force);

        $this->info('✅ Docker configuration successfully generated!');

        return Command::SUCCESS;
    }

    /**
     * Create necessary directories if they don't exist.
     */
    private function createDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("Created directory: <info>{$directory}</info>");
            }
        }
    }

    /**
     * Get the default Git branch (main or master).
     */
    private function getDefaultGitBranch(): string
    {
        // Try to detect the default branch from the Git repository
        if (File::exists(base_path('.git'))) {
            // Check for main branch
            if (File::exists(base_path('.git/refs/heads/main'))) {
                return 'main';
            }

            // Check for master branch
            if (File::exists(base_path('.git/refs/heads/master'))) {
                return 'master';
            }
        }

        // Default to 'main' if we can't determine
        return 'main';
    }

    /**
     * Detect PHP extensions required from composer.json.
     */
    private function detectPhpExtensions(): array
    {
        $composerJson = json_decode(File::get(base_path('composer.json')), true);
        $extensions = [];

        // Look for ext-* requirements in require section
        if (isset($composerJson['require'])) {
            foreach ($composerJson['require'] as $requirement => $version) {
                if (str_starts_with($requirement, 'ext-')) {
                    $extensions[] = str_replace('ext-', '', $requirement);
                }
            }
        }

        // Add common extensions if not already detected
        $commonExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'exif', 'pcntl', 'bcmath', 'gd'];

        foreach ($commonExtensions as $ext) {
            if (! in_array($ext, $extensions)) {
                $extensions[] = $ext;
            }
        }

        return $extensions;
    }

    /**
     * Generate docker-compose.yml file.
     */
    private function generateDockerComposeFile(string $configDirectory, bool $force): void
    {
        $filePath = base_path('docker-compose.yml');

        if (! $force && File::exists($filePath)) {
            $this->line('Skipping <info>docker-compose.yml</info> (already exists, use --force to overwrite)');

            return;
        }

        $services = [
            MySQLDockerService::class,
            RedisDockerService::class
        ];

        $compose = [];

        foreach ($services as $service) {
            $serviceInstance = new $service();
            $compose['services'][$serviceInstance->getServiceName()] = $serviceInstance->getService()->toArray();
        }

        // Iterate through the services and compile networks and volumes
        $networks = [];
        $volumes = [];

        foreach ($compose['services'] as $serviceName => $service) {
            if (isset($service['networks'])) {
                foreach ($service['networks'] as $network) {
                    $networks[$network] = [];
                }
            }

            if (isset($service['volumes'])) {
                foreach ($service['volumes'] as $volume) {
                    // Split the volume name if it contains a colon
                    if (str_contains($volume, ':')) {
                        $volume = explode(':', $volume)[0];
                    }

                    $volumes[$volume] = [];
                }
            }
        }

        // Add networks and volumes to the compose file
        $compose['networks'] = array_keys($networks);
        $compose['volumes'] = array_keys($volumes);

        // Use Symfony YAML to generate clean output
        $yamlContent = Yaml::dump($compose, 6, 2);

        File::put($filePath, $yamlContent);
        $this->line('Generated: <info>docker-compose.yml</info>');
    }

    /**
     * Generate Dockerfile for the Laravel app.
     */
    private function generateDockerfile(string $configDirectory, array $extensions, bool $force): void
    {
        $filePath = base_path("$configDirectory/app.dockerfile");

        if (! $force && File::exists($filePath)) {
            $this->line("Skipping <info>$configDirectory/Dockerfile</info> (already exists, use --force to overwrite)");

            return;
        }

        $template = File::get(__DIR__.'/../../stubs/app.dockerfile.stub');

        // Build PHP extensions installation commands
        $extensionsCode = '';
        foreach ($extensions as $extension) {
            switch ($extension) {
                case 'pdo_mysql':
                    $extensionsCode .= "RUN docker-php-ext-install pdo_mysql\n";
                    break;
                case 'gd':
                    $extensionsCode .= "RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp\n";
                    $extensionsCode .= "RUN docker-php-ext-install -j$(nproc) gd\n";
                    break;
                case 'zip':
                    $extensionsCode .= "RUN docker-php-ext-install zip\n";
                    break;
                default:
                    $extensionsCode .= "RUN docker-php-ext-install $extension\n";
            }
        }

        // Replace placeholder with detected extensions
        $template = str_replace('# [DOCKERIZER_PLACEHOLDER_EXTENSIONS]', mb_trim($extensionsCode), $template);

        File::put($filePath, $template);
        $this->line("Generated: <info>$configDirectory/Dockerfile</info>");
    }

    /**
     * Generate Nginx Dockerfile.
     */
    private function generateNginxDockerfile(string $configDirectory, bool $force): void
    {
        $filePath = base_path("$configDirectory/nginx.dockerfile");

        if (! $force && File::exists($filePath)) {
            $this->line("Skipping <info>$configDirectory/nginx.dockerfile</info> (already exists, use --force to overwrite)");

            return;
        }

        $template = File::get(__DIR__.'/../../stubs/nginx.dockerfile.stub');
        $template = str_replace('.dockerizer', $configDirectory, $template);

        File::put($filePath, $template);
        $this->line("Generated: <info>$configDirectory/nginx.Dockerfile</info>");
    }

    /**
     * Generate entrypoint.sh script.
     */
    private function generateEntrypointScript(string $configDirectory, bool $force): void
    {
        $filePath = base_path("$configDirectory/entrypoint.sh");

        if (! $force && File::exists($filePath)) {
            $this->line("Skipping <info>$configDirectory/entrypoint.sh</info> (already exists, use --force to overwrite)");

            return;
        }

        $template = File::get(__DIR__.'/../../stubs/entrypoint.sh.stub');

        File::put($filePath, $template);
        chmod($filePath, 0755); // Make sure it's executable
        $this->line("Generated: <info>$configDirectory/entrypoint.sh</info>");
    }

    /**
     * Generate Nginx configuration file.
     */
    private function generateNginxConfig(string $configDirectory, bool $force): void
    {
        $filePath = base_path("$configDirectory/nginx.conf");

        if (! $force && File::exists($filePath)) {
            $this->line("Skipping <info>$configDirectory/nginx.conf</info> (already exists, use --force to overwrite)");

            return;
        }

        $template = File::get(__DIR__.'/../../stubs/default.conf.stub');

        File::put($filePath, $template);
        $this->line("Generated: <info>$configDirectory/nginx.conf</info>");
    }
}
