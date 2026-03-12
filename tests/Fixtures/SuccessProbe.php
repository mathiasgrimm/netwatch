<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Tests\Fixtures;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

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
