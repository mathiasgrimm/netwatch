<?php

declare(strict_types=1);

namespace MathiasGrimm\Netwatch\Tests\Fixtures;

use MathiasGrimm\Netwatch\Contract\ProbeInterface;
use MathiasGrimm\Netwatch\Result\ProbeResult;

class SuccessProbe implements ProbeInterface
{
    public function probe(): ProbeResult
    {
        return new ProbeResult(
            connectMs: 1.0,
            requestMs: 2.0,
            totalMs: 3.0,
            success: true,
        );
    }

    public function name(): string
    {
        return 'success-probe';
    }
}
