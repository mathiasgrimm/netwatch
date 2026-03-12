<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Tests\Fixtures;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

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
