<?php

declare(strict_types = 1);

namespace McMatters\CsFixer\Console\Commands;

use Illuminate\Console\Command;
use McMatters\CsFixer\Steps\AddClassDocBlock;
use McMatters\CsFixer\Steps\DeclareStrictTypes;
use McMatters\CsFixer\Steps\NormalizePhpDoc;
use McMatters\CsFixer\Steps\ReplacePathHelpers;

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
     */
    protected function getSteps(): array
    {
        return [
            new DeclareStrictTypes(),
            new AddClassDocBlock(),
            new ReplacePathHelpers(),
            new NormalizePhpDoc(),
        ];
    }
}
