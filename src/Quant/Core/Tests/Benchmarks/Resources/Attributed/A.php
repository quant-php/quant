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

namespace Quant\Core\Tests\Benchmarks\Resources\Attributed;

use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use Quant\Core\Lang\Modifier;
use Quant\Core\Trait\AccessorTrait;

/**
 *
 */
class A
{
    use AccessorTrait;

    #[Getter(Modifier::PUBLIC)] #[Setter(Modifier::PUBLIC)]
    private string $aPublic = "aPublic";


    public function __construct(
        #[Getter(Modifier::PRIVATE)] #[Setter(Modifier::PRIVATE)] private ?string $aPrivate = null,
        #[Getter(Modifier::PROTECTED)] #[Setter(Modifier::PROTECTED)] private ?string $aProtected = null
    ) {
        $this->applyProperties(func_get_args());
    }


    private function applyAPrivate(string $s): string
    {
        return "string";
    }


    public function proxySetAPrivate(string $s): A
    {
        return $this->setAPrivate($s);
    }

    public function proxyGetAPrivate(): string
    {
        return $this->getAPrivate();
    }
}
