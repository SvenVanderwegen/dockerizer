<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
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
        $this->generateGithubWorkflow($gitBranch, $force);

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
        $commonExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'exif', 'pcntl', 'bcmath', 'gd', 'zip'];

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
            $this->line("Skipping <info>docker-compose.yml</info> (already exists, use --force to overwrite)");
            return;
        }

        // Build docker-compose configuration
        $dockerCompose = [
            'version' => '3.8',
            'services' => [
                'app' => [
                    'build' => [
                        'context' => '.',
                        'dockerfile' => "$configDirectory/Dockerfile",
                    ],
                    'container_name' => '${APP_NAME:-laravel}_app',
                    'restart' => 'unless-stopped',
                    'volumes' => [
                        '.:/var/www/html',
                        "./$configDirectory/php.ini:/usr/local/etc/php/conf.d/custom.ini",
                    ],
                    'networks' => ['laravel'],
                    'depends_on' => ['db'],
                ],
                'nginx' => [
                    'build' => [
                        'context' => '.',
                        'dockerfile' => "$configDirectory/nginx.Dockerfile",
                    ],
                    'container_name' => '${APP_NAME:-laravel}_nginx',
                    'restart' => 'unless-stopped',
                    'ports' => [
                        '${APP_PORT:-80}:80',
                    ],
                    'volumes' => [
                        '.:/var/www/html',
                        "./$configDirectory/nginx.conf:/etc/nginx/conf.d/default.conf",
                    ],
                    'networks' => ['laravel'],
                    'depends_on' => ['app'],
                ],
                'db' => [
                    'image' => 'mysql:8.0',
                    'container_name' => '${APP_NAME:-laravel}_db',
                    'restart' => 'unless-stopped',
                    'ports' => [
                        '${FORWARD_DB_PORT:-3306}:3306',
                    ],
                    'environment' => [
                        'MYSQL_DATABASE' => '${DB_DATABASE:-laravel}',
                        'MYSQL_ROOT_PASSWORD' => '${DB_PASSWORD:-secret}',
                        'MYSQL_PASSWORD' => '${DB_PASSWORD:-secret}',
                        'MYSQL_USER' => '${DB_USERNAME:-laravel}',
                    ],
                    'volumes' => [
                        'mysql_data:/var/lib/mysql',
                    ],
                    'networks' => ['laravel'],
                ],
            ],
            'networks' => [
                'laravel' => [
                    'driver' => 'bridge',
                ],
            ],
            'volumes' => [
                'mysql_data' => [
                    'driver' => 'local',
                ],
            ],
        ];

        // Use Symfony YAML to generate clean output
        $yamlContent = Yaml::dump($dockerCompose, 6, 2);

        File::put($filePath, $yamlContent);
        $this->line("Generated: <info>docker-compose.yml</info>");
    }

    /**
     * Generate Dockerfile for the Laravel app.
     */
    private function generateDockerfile(string $configDirectory, array $extensions, bool $force): void
    {
        $filePath = base_path("$configDirectory/Dockerfile");

        if (! $force && File::exists($filePath)) {
            $this->line("Skipping <info>$configDirectory/Dockerfile</info> (already exists, use --force to overwrite)");
            return;
        }

        $template = File::get(__DIR__ . '/../../stubs/app.dockerfile.stub');

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
        $template = str_replace('# [DOCKERIZER_PLACEHOLDER_EXTENSIONS]', trim($extensionsCode), $template);

        File::put($filePath, $template);
        $this->line("Generated: <info>$configDirectory/Dockerfile</info>");
    }

    /**
     * Generate Nginx Dockerfile.
     */
    private function generateNginxDockerfile(string $configDirectory, bool $force): void
    {
        $filePath = base_path("$configDirectory/nginx.Dockerfile");

        if (! $force && File::exists($filePath)) {
            $this->line("Skipping <info>$configDirectory/nginx.Dockerfile</info> (already exists, use --force to overwrite)");
            return;
        }

        $template = File::get(__DIR__ . '/../../stubs/nginx.dockerfile.stub');
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

        $content = <<<'EOT'
#!/bin/bash
set -e

# Set correct permissions
echo "Setting correct permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run the PHP-FPM command passed as argument
exec "$@"
EOT;

        File::put($filePath, $content);
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

        $template = File::get(__DIR__ . '/../../stubs/default.conf.stub');

        File::put($filePath, $template);
        $this->line("Generated: <info>$configDirectory/nginx.conf</info>");
    }

    /**
     * Generate GitHub Actions workflow file.
     */
    private function generateGithubWorkflow(string $gitBranch, bool $force): void
    {
        $filePath = base_path('.github/workflows/docker-build.yml');

        if (! $force && File::exists($filePath)) {
            $this->line("Skipping <info>.github/workflows/docker-build.yml</info> (already exists, use --force to overwrite)");
            return;
        }

        $content = <<<EOT
name: Build and Deploy Docker Images

on:
  push:
    branches:
      - $gitBranch
  workflow_dispatch:

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Login to Docker Hub
        uses: docker/login-action@v3
        with:
          username: \${{ secrets.DOCKER_USERNAME }}
          password: \${{ secrets.DOCKER_PASSWORD }}

      - name: Extract metadata for app image
        id: meta-app
        uses: docker/metadata-action@v5
        with:
          images: \${{ secrets.DOCKER_USERNAME }}/app

      - name: Extract metadata for nginx image
        id: meta-nginx
        uses: docker/metadata-action@v5
        with:
          images: \${{ secrets.DOCKER_USERNAME }}/nginx

      - name: Build and push app image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: .dockerizer/Dockerfile
          push: true
          tags: \${{ steps.meta-app.outputs.tags }}
          labels: \${{ steps.meta-app.outputs.labels }}

      - name: Build and push nginx image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: .dockerizer/nginx.Dockerfile
          push: true
          tags: \${{ steps.meta-nginx.outputs.tags }}
          labels: \${{ steps.meta-nginx.outputs.labels }}
EOT;

        File::put($filePath, $content);
        $this->line("Generated: <info>.github/workflows/docker-build.yml</info>");
    }
}
