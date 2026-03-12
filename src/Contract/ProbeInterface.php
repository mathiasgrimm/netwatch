<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Contract;

use Mathiasgrimm\Netwatch\Result\ProbeResult;

interface ProbeInterface
{
    public function probe(): ProbeResult;

    public function name(): string;
}
