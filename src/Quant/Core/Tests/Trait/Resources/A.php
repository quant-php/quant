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

use Quant\Core\Trait\AccessorTrait;
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
    use AccessorTrait;

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
