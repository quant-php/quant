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

class A extends ParentA
{
    #[Getter] #[Setter]
    private string $foo;

    #[Getter] #[Setter]
    private string $bar;

    #[Getter(Modifier::PRIVATE)] #[Setter(Modifier::PRIVATE)]
    private string $privateBar;

    #[Getter(Modifier::PROTECTED)] #[Setter(Modifier::PROTECTED)]
    private string $protectedBar;

    #[Getter]
    private string $snafu = "snafu";

    private string $neverRead = "foo";

    private string $unused;

    public function __construct(
        #[Getter]
        private string $constructorProperty = "1",
        #[Setter]
        private string $propertySet = "1"
    ) {
    }

    private function proxyGetFoo(): A
    {
        return $this->getFoo();
    }
}
