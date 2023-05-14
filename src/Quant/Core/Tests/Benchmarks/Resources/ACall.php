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

namespace Quant\Core\Tests\Benchmarks\Resources;

class ACall
{
    public string $a = "";

    public function __call($method, $args)
    {
        $this->{$method} = $args[0];
    }
}
