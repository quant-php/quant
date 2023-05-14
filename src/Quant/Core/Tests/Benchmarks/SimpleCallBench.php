<?php

/**
 * This file is part of the quant project.
 *
 * (c) 2023 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

declare(strict_types=1);

namespace Quant\Core\Tests\Benchmarks;

use Quant\Core\Tests\Benchmarks\Resources\ACall;
use Quant\Core\Tests\Benchmarks\Resources\ANoCall;

class SimpleCallBench
{
    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchClassMagic(): void
    {
        $a = new ACall();
        $a->a("foo");
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchNoClassMagic(): void
    {
        $a = new ANoCall();
        $a->setA("foo");
    }
}
