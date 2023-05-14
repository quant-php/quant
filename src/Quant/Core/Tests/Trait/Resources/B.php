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

namespace Quant\Core\Tests\Trait\Resources;

use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use Quant\Core\Lang\Modifier;

/**
 * @method string getGuarded()
 * @method B setGuarded(string $f)
 *
 * @method bool isValid()
 * @method B setValid(bool $b)
 *
 * @method string getFoobar()
 * @method B setFoobar(string $s)
 *
 * @method B setPublicGuard(string $s)
 * @method string getPublicGuard()
 *
 */
class B extends A
{
    /* @phpstan-ignore-next-line */
    #[Getter(Modifier::PRIVATE)] #[Setter(Modifier::PRIVATE)] private bool $valid = false;

    #[Getter] #[Setter]
    public string $foobar = "Ok";

    /* @phpstan-ignore-next-line */
    #[Setter(Modifier::PUBLIC)] #[Getter(Modifier::PUBLIC)] private string $guarded = "";

    /* @phpstan-ignore-next-line */
    public function __construct(string $foo, $noArg = true)
    {
        parent::__construct($foo);
    }

    public function proxyIsValid(): bool
    {
        return $this->isValid();
    }

    public function proxySetValid(bool $b): B
    {
        return $this->setValid($b);
    }

    /* @phpstan-ignore-next-line */
    private function applyGuarded(string $value): string
    {
        return $value;
    }

    protected function applyPublicGuard(string $f): string
    {
        return "overridden in child";
    }
}
