<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Steps;

use McMatters\CsFixer\Contracts\Step;

use function array_replace_recursive;

class AbstractStep implements Step
{
    public function __construct(protected array $config = [])
    {
    }

    public function handle(array $config = []): void
    {
        $this->config = array_replace_recursive($this->config, $config);
    }
}
