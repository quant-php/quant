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

use Quant\Core\Tests\Benchmarks\Resources\Implemented\A ;
use Quant\Core\Tests\Benchmarks\Resources\Implemented\B;
use Quant\Core\Tests\Benchmarks\Resources\Implemented\C;

class ImplementedBench
{
    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchAttributesNotUsed(): void
    {

        $a = new A(
            aPrivate: "priv",
            aProtected: "prot"
        );

        $b = new B();
        $b->proxySetAPrivate("fromB");
        $b->proxyGetAPrivate();
        $b->getBPublic();


        $c = new C();

        $c->setCPublic("fromC");
    }
}
