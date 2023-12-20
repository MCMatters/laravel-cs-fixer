<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Console\Commands;

use Illuminate\Console\Command;
use McMatters\CsFixer\Steps\DeclareStrictTypes;
use McMatters\CsFixer\Steps\NormalizePhpDoc;
use McMatters\CsFixer\Steps\RemoveAutoDiscovering;
use McMatters\CsFixer\Steps\ReplacePathHelpers;

class FixCommand extends Command
{
    protected $name = 'cs-fixer:fix';

    protected $description = 'Fix code styling';

    /**
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     */
    public function handle(): int
    {
        /** @var \McMatters\CsFixer\Contracts\Step $step */
        foreach ($this->getSteps() as $step) {
            $step->handle();
        }

        $this->info('Operation completed successfully');

        return self::SUCCESS;
    }

    /**
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     */
    protected function getSteps(): array
    {
        return [
            new DeclareStrictTypes(),
            new ReplacePathHelpers(),
            new NormalizePhpDoc(),
            new RemoveAutoDiscovering($this->getLaravel()),
        ];
    }
}
