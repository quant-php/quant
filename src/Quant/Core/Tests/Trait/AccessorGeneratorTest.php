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

namespace Quant\Core\Tests\Trait;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Quant\Core\Tests\Trait\Resources\A;
use Quant\Core\Tests\Trait\Resources\B;
use Quant\Core\Tests\Trait\Resources\C;
use Quant\Core\Tests\Trait\Resources\ClassHasAttributes;
use ValueError;

class AccessorGeneratorTest extends TestCase
{
    public function testA(): void
    {

        $args_1 = [
            "foo" => "Hello World"
        ];
        $args_2 = [
            "foo" => "Hello World!"
        ];

        $inst = $this->createA($args_1);
        $anotherInst = $this->createA($args_2);

        $this->assertSame($inst, $inst->setFoo("foo"));

        $this->assertSame($inst, $inst->setFoobar("Ok"));
        $this->assertSame("Ok", $inst->foobar);

        $this->assertSame($anotherInst, $anotherInst->setFoo("foo"));
        $this->assertSame("foo", $anotherInst->getFoo());
        $this->assertSame("foo", $inst->getFoo());
        $this->assertSame("foo", $inst->getFoo());

        // guarded
        $this->assertSame($anotherInst, $anotherInst->setFoo("noset"));
        $this->assertSame("foo", $anotherInst->getFoo());
    }


    public function testSettersAndGettersForClass(): void
    {
        $inst = $this->createClassWithSetterAndGetterAttributes([
            "foo" => "Hello World",
            "bar" => "World Hello"
        ]);


        $inst->setFoo("foo")->setBar("bar")->setSnafu("snafu")->setFoobar("foobar");

        $this->assertSame("foo", $inst->getFoo());
        $this->assertSame("bar", $inst->getBar());
        $this->assertSame("snafu", $inst->getSnafu());
        $this->assertSame("foobar", $inst->getFoobar());
    }


    public function testAccessorGeneratorWithDifferentValues(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage(">= 2");

        $inst = $this->createA();

        $this->assertSame($inst, $inst->setValueErrorTrigger(1));
    }

    public function testGetProtectedPropertyOnA(): void
    {
        $this->expectException(BadMethodCallException::class);

        $inst = $this->createA();
        $inst->getProtectedVar();
    }

    public function testSetProtectedPropertyOnA(): void
    {
        $this->expectException(BadMethodCallException::class);
        $inst = $this->createA();
        $inst->setProtectedVar("foo");
    }

    public function testB(): void
    {
        $args_1 = [
            "foo" => "Hello World"
        ];

        $inst = $this->createB($args_1);
        $this->assertSame("protected", $inst->proxyProtectedVar());

        $this->assertSame("Hello World", $inst->getFoo());

        $inst->setFoobar("oofrab");
        $this->assertSame("oofrab", $inst->getFoobar());
        $inst->getFoobar();

        $inst->setPrivateVar(123);
        $this->assertSame(123, $inst->getPrivateVar());
    }

    public function testC(): void
    {
        $args_1 = [
            "foo" => "Hello World"
        ];

        $inst = $this->createC($args_1);

        $inst->setPrivateVar(123);
        $this->assertSame(123, $inst->getPrivateVar());

        $this->assertNotSame("updated", $inst->proxyProtectedVar());
        $inst->setProxyProtectedVar("updated");
        $this->assertSame("updated", $inst->proxyProtectedVar());
    }



    public function testProtectedPropertyWithProxy(): void
    {
        $inst = $this->createA();
        $this->assertSame("protected", $inst->proxyProtectedVar());
    }


    public function testBadMethodCallExceptionOnSetNotExisting(): void
    {
        $this->expectException(BadMethodCallException::class);
        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->setBar("missing");
    }


    public function testBadMethodCallExceptionOnSetNotAttributed(): void
    {
        $this->expectException(BadMethodCallException::class);

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->setSnafu(true);
    }


    public function testBadMethodCallExceptionOnNotSetPrefixed(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("not found");

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->snafu();
    }

    public function testBadMethodCallExceptionOnGetNotExisting(): void
    {
        $this->expectException(BadMethodCallException::class);

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->getBar();
    }


    public function testBadMethodCallExceptionOnGetNotAttributed(): void
    {
        $this->expectException(BadMethodCallException::class);

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->getSnafu();
    }


    public function testBadMethodCallExceptionOnNotGetPrefixed(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("not found");

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->snafu();
    }


    /**
     * @param array<string, string> $data
     *
     * @return A
     */
    protected function createA(array $data = ["foo" => "bar"]): object
    {
        return new A(...$data);
    }

    /**
     * @param array<string, string> $data
     *
     * @return B
     */
    protected function createB(array $data): object
    {
        return new B(...$data);
    }

    /**
     * @param array<string, string> $data
     *
     * @return C
     */
    protected function createC(array $data): object
    {
        return new C(...$data);
    }


    /**
     * @param array<string, string> $data
     *
     * @return ClassHasAttributes
     */
    protected function createClassWithSetterAndGetterAttributes(array $data): object
    {
        return new ClassHasAttributes(...$data);
    }
}
