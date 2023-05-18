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

namespace Quant\PHPStan\Tests\Data;

use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use Quant\Core\Lang\Modifier;
use Quant\Core\Trait\AccessorTrait;

#[Getter(Modifier::PROTECTED)]
#[Setter]
class D
{
    use AccessorTrait;

    private string $foo;

    private string $bar;
}


class ChildD extends D
{
    private string $foo;

    private string $bar;

    public function run()
    {
        $d = new D();
        $d->getFoo();

        $d->setBar("value")->getFoo();
    }
}
