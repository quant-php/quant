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

use Quant\Core\Trait\AccessorGenerator;
use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use Quant\Core\Lang\Modifier;
use ValueError;

/**
 * @method A setPrivateVar(int $b)
 * @method int getPrivateVar()
 * @method A setFoo(string $a)
 * @method A setFoobar(string $b)
 * @method string getFoo()
 * @method string getProtectedVar()
 * @method A setProtectedVar(string $a)
 * @method A setValueErrorTrigger(int $a).
 * @method bool isBool()
 * @method string getNotBool()
 * @method A setProtectedGuard(string $s)
 * @method string getProtectedGuard()
 */
class A
{
    use AccessorGenerator;

    #[Getter(Modifier::PROTECTED)] #[Setter(Modifier::PROTECTED)]
    private string $protectedVar = "protected";

    #[Setter]
    private int $valueErrorTrigger;

    #[Setter]
    public string $foobar = "Ok";

    #[Setter] #[Getter]
    private int $privateVar;

    #[Getter]
    private bool $bool = true;

    #[Getter]
    private string $notBool = "true";

    #[Setter] #[Getter]
    private string $protectedGuard = "";

    private string $snafu;
    public function __construct(
        #[Setter] #[Getter]
        private string $foo
    ) {
        $this->applyProperties(func_get_args());
    }


    protected function applyFoo(mixed $value): mixed
    {
        if ($value !== "noset") {
            return $value;
        }

        return $this->foo;
    }


    protected function applyValueErrorTrigger(int $value): int
    {
        if ($value < 2) {
            throw new ValueError("invalid value for valueErrorTrigger: must be >= 2");
        }
        return $value;
    }

    public function proxyProtectedVar(): string
    {
        return $this->getProtectedVar();
    }

    public function setProxyProtectedVar(string $a): A
    {
        return $this->setProtectedVar($a);
    }

    protected function applyProtectedGuard(string $f): string
    {
        return "set in parent";
    }
}
