<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Tests\Fixtures;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

class CountingProbe implements ProbeInterface
{
    public static int $calls = 0;

    public function probe(): ProbeResult
    {
        self::$calls++;

        return new ProbeResult(
            connectMs: 1.0,
            requestMs: 2.0,
            totalMs: 3.0,
            success: true,
        );
    }

    public function name(): string
    {
        return 'counting-probe';
    }
}
