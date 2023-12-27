<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Managers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;
use McMatters\CsFixer\Steps\DeclareStrictTypesStep;
use McMatters\CsFixer\Steps\NormalizePhpDocStep;
use McMatters\CsFixer\Steps\RemoveAutoDiscoveringStep;
use McMatters\CsFixer\Steps\ReplacePathHelpersStep;

use function array_keys;
use function class_basename;

class StepManager
{
    /**
     * @var \McMatters\CsFixer\Contracts\Step[]
     */
    protected array $steps = [];

    public function __construct()
    {
        $this->setSteps();
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getStepsKeys(): array
    {
        return array_keys($this->steps);
    }

    public function runSteps(): void
    {
        foreach ($this->getSteps() as $step) {
            $step->handle();
        }
    }

    public function runStep(string $step, array $config = []): void
    {
        if (!isset($this->steps[$step])) {
            throw new InvalidArgumentException('Step does not exist');
        }

        $this->steps[$step]->handle($config);
    }

    protected function setSteps(): void
    {
        $steps = [
            DeclareStrictTypesStep::class,
            ReplacePathHelpersStep::class,
            NormalizePhpDocStep::class,
            RemoveAutoDiscoveringStep::class,
        ];

        foreach ($steps as $step) {
            $snakeCaseStep = Str::snake(class_basename($step));

            $this->steps[$step] = new $step(Config::get("cs-fixer.{$snakeCaseStep}", []));
        }
    }
}
