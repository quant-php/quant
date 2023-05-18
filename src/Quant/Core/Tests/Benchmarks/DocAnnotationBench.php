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

use Quant\Core\Tests\Benchmarks\Resources\DocAnnotations\A;
use Quant\Core\Tests\Benchmarks\Resources\DocAnnotations\B;

class DocAnnotationBench
{
    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchWithDocAnnotation(): void
    {

        $a = new A();

        $a->aPublic();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchSimpleWithTrait(): void
    {

        $b = new B();

        $b->getBPublic();
    }
}
