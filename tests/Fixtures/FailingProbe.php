<?php

declare(strict_types=1);

namespace MathiasGrimm\Netwatch\Tests\Fixtures;

use MathiasGrimm\Netwatch\Contract\ProbeInterface;
use MathiasGrimm\Netwatch\Result\ProbeResult;

class FailingProbe implements ProbeInterface
{
    public function probe(): ProbeResult
    {
        return new ProbeResult(
            connectMs: 100.0,
            requestMs: 0,
            totalMs: 100.0,
            success: false,
            error: 'Connection refused',
        );
    }

    public function name(): string
    {
        return 'failing-probe';
    }
}
