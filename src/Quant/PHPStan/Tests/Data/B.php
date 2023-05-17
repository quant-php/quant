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

class B extends A
{
    private string $value = "2";

    public function proxySetBar(): A
    {
        return $this->setBar("1");
    }

    public function proxySetPrivateBar(): A
    {
        return $this->setPrivateBar("1");
    }

    public function proxyGetPrivateBar(): string
    {
        return $this->getPrivateBar();
    }

    public function proxySetProtectedBar(): A
    {
        return $this->setProtectedBar("1");
    }

    public function proxyGetProtectedBar(): string
    {
        return $this->getProtectedBar();
    }

    public function run()
    {
        $a = new A();

        $c = new C();
        $c->getProtectedBarC();

        $this->getProtectedBar();

        $b = new B();
        $b->getProtectedBar();
        $a->getProtectedBar();

        $this->notExisting();
    }
}
