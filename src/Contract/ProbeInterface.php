<?php

declare(strict_types=1);

namespace MathiasGrimm\Netwatch\Contract;

use MathiasGrimm\Netwatch\Result\ProbeResult;

interface ProbeInterface
{
    public function probe(): ProbeResult;

    public function name(): string;
}
