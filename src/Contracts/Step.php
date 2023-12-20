<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Contracts;

interface Step
{
    public function handle(): void;
}
