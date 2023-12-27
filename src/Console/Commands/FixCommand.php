<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Console\Commands;

use Illuminate\Console\Command;
use McMatters\CsFixer\Managers\StepManager;

class FixCommand extends Command
{
    protected $name = 'cs-fixer:fix';

    protected $description = 'Fix code styling';

    public function handle(StepManager $stepManager): int
    {
        $stepManager->runSteps();

        $this->info('Operation completed successfully');

        return self::SUCCESS;
    }
}
