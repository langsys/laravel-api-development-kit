<?php

namespace Langsys\ApiKit\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'api-kit:install
        {--with-migrations : Publish database migrations for DB-driven resource metadata and API keys}';

    protected $description = 'Install the API Development Kit package';

    public function handle(): int
    {
        $this->info('Installing API Development Kit...');

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => 'api-kit-config',
        ]);
        $this->info('Published config/api-kit.php');

        // Publish migrations if requested
        if ($this->option('with-migrations')) {
            $this->call('vendor:publish', [
                '--tag' => 'api-kit-migrations',
            ]);
            $this->info('Published database migrations');
        }

        // Publish documentation
        $this->call('vendor:publish', [
            '--tag' => 'api-kit-docs',
        ]);
        $this->info('Published CLAUDE.md template and conventions guide');

        // Publish stubs
        $this->call('vendor:publish', [
            '--tag' => 'api-kit-stubs',
        ]);
        $this->info('Published example stubs');

        $this->newLine();
        $this->info('API Development Kit installed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Review config/api-kit.php and adjust settings');
        $this->line('  2. Register middleware in your HTTP kernel or bootstrap');
        $this->line('  3. Check CLAUDE.md for AI-assisted development patterns');

        if (!$this->option('with-migrations')) {
            $this->newLine();
            $this->line('  Tip: Run with --with-migrations to enable DB-driven resource metadata');
        }

        return self::SUCCESS;
    }
}
