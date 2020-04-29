<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Console\Commands;

use Illuminate\Console\Command;
use McMatters\CsFixer\Steps\{
    AddClassDocBlock, DeclareStrictTypes, NormalizePhpDoc,
    RemoveAutodiscovering, ReplacePathHelpers
};

/**
 * Class FixCommand
 *
 * @package McMatters\CsFixer\Console\Commands
 */
class FixCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'cs-fixer:fix';

    /**
     * @var string
     */
    protected $description = 'Fix code styling';

    /**
     * @return void
     *
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     */
    public function handle(): void
    {
        /** @var \McMatters\CsFixer\Contracts\Step $step */
        foreach ($this->getSteps() as $step) {
            $step->handle();
        }

        $this->info('Operation completed successfully');
    }

    /**
     * @return array
     *
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     */
    protected function getSteps(): array
    {
        return [
            new DeclareStrictTypes(),
            new AddClassDocBlock(),
            new ReplacePathHelpers(),
            new NormalizePhpDoc(),
            new RemoveAutoDiscovering($this->getLaravel())
        ];
    }
}
