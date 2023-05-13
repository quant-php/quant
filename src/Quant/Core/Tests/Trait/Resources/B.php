<?php

/**
 * MIT License
 *
 * Copyright (c) 2023 Thorsten Suckow-Homberg https://github.com/quant-php/quant
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
}
