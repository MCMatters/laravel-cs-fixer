<?php

declare(strict_types=1);

namespace McMatters\CsFixer;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use McMatters\CsFixer\Console\Commands\FixCommand;

use function function_exists;
use function method_exists;

/**
 * Class ServiceProvider
 *
 * @package McMatters\CsFixer
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $configPath = __DIR__.'/../config/cs-fixer.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $configPath => $this->configPath().DIRECTORY_SEPARATOR.'cs-fixer.php',
            ], 'config');
        }

        $this->mergeConfigFrom($configPath, 'cs-fixer');
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('command.cs-fixer.fix', static function () {
            return new FixCommand();
        });

        $this->commands([
            'command.cs-fixer.fix',
        ]);
    }

    /**
     * @return string
     */
    protected function configPath(): string
    {
        if (method_exists($this->app, 'configPath')) {
            return $this->app->configPath();
        }

        return function_exists('config_path')
            ? config_path()
            : $this->app->basePath().DIRECTORY_SEPARATOR.'config';
    }
}
