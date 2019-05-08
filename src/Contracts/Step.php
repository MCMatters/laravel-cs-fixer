<?php

declare(strict_types = 1);

namespace McMatters\CsFixer\Contracts;

/**
 * Interface Step
 *
 * @package McMatters\CsFixer\Contracts
 */
interface Step
{
    /**
     * @return void
     */
    public function handle(): void;
}
