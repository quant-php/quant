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

class GetterTraitTest extends TestCase
{
    public function testGetterTrait(): void
    {
        $args_1 = [
            "foo" => "Hello World"
        ];
        $args_2 = [
            "foo" => "Hello World!"
        ];

        $class = $this->createClassWithTrait($args_1);
        $anotherClass = $this->createClassWithTrait($args_2);
        
        $this->assertSame($args_1["foo"], $class->getFoo());
        $this->assertSame("Ok", $class->getFoobar());
        $this->assertSame($args_2["foo"], $anotherClass->getFoo());
        $this->assertSame($args_1["foo"], $class->getFoo());
    }


    public function testBadMethodCallExceptionOnNotExisting(): void
    {
        $this->expectException(BadMethodCallException::class);

        $class = $this->createClassWithTrait(["foo" => "Hello World"]);

        /* @phpstan-ignore-next-line*/
        $class->getBar();
    }


    public function testBadMethodCallExceptionOnNotAttributed(): void
    {
        $this->expectException(BadMethodCallException::class);

        $class = $this->createClassWithTrait(["foo" => "Hello World"]);

        /* @phpstan-ignore-next-line*/
        $class->getSnafu();
    }


    public function testBadMethodCallExceptionOnNotGetPrefixed(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("not considered");

        $class = $this->createClassWithTrait(["foo" => "Hello World"]);

        /* @phpstan-ignore-next-line*/
        $class->snafu();
    }

    /**
     * @param array<string, string> $data
     *
     * @return WithGetterTrait
     */
    protected function createClassWithTrait(array $data): object
    {
        return new WithGetterTrait(...$data);
    }
}
