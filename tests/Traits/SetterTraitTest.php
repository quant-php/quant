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

namespace Tests\Quant\Traits;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;

class SetterTraitTest extends TestCase
{
    public function testSetterTrait(): void
    {
        $args_1 = [
            "foo" => "Hello World"
        ];
        $args_2 = [
            "foo" => "Hello World!"
        ];

        $class = $this->createClassWithTrait($args_1);
        $anotherClass = $this->createClassWithTrait($args_2);

        $this->assertSame($class, $class->setFoo("foo"));

        $this->assertSame($class, $class->setFoobar("Ok"));
        $this->assertSame("Ok", $class->foobar);

        $this->assertSame($anotherClass, $anotherClass->setFoo("foo"));
        $this->assertSame("foo", $anotherClass->foo);
        $this->assertSame("foo", $class->foo);

        // guarded
        $this->assertSame($anotherClass, $anotherClass->setFoo("noset"));
        $this->assertSame("foo", $anotherClass->foo);
    }


    public function testBadMethodCallExceptionOnNotExisting(): void
    {
        $this->expectException(BadMethodCallException::class);

        $class = $this->createClassWithTrait(["foo" => "Hello World"]);

        /* @phpstan-ignore-next-line*/
        $class->setBar("missing");
    }


    public function testBadMethodCallExceptionOnNotAttributed(): void
    {
        $this->expectException(BadMethodCallException::class);

        $class = $this->createClassWithTrait(["foo" => "Hello World"]);

        /* @phpstan-ignore-next-line*/
        $class->setSnafu(true);
    }


    public function testBadMethodCallExceptionOnNotSetPrefixed(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("not found");

        $class = $this->createClassWithTrait(["foo" => "Hello World"]);

        /* @phpstan-ignore-next-line*/
        $class->snafu();
    }

    /**
     * @param array<string, string> $data
     *
     * @return WithSetterTrait
     */
    protected function createClassWithTrait(array $data): object
    {
        return new WithSetterTrait(...$data);
    }
}
