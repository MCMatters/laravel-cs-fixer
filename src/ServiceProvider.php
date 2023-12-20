<?php

declare(strict_types=1);

namespace McMatters\CsFixer;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use McMatters\CsFixer\Console\Commands\FixCommand;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $configPath = __DIR__.'/../config/cs-fixer.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $configPath => $this->app->configPath().DIRECTORY_SEPARATOR.'cs-fixer.php',
            ], 'config');
        }

        $this->mergeConfigFrom($configPath, 'cs-fixer');
    }

    public function register(): void
    {
        $this->app->singleton('command.cs-fixer.fix', static fn () => new FixCommand());

        $this->commands(['command.cs-fixer.fix']);
    }
}
