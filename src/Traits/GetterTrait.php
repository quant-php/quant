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

namespace Quant\Traits;

use BadMethodCallException;
use Quant\Attributes\Getter;
use ReflectionClass;

/**
 * Provides read-access to constructor property promoted object members with the Getter-attribute.
 *
 * @example
 *    use Getter;
 *    use GetterTrait;
 *
 *    class Target {
 *         trait GetterTrait;
 *
 *         #[Getter]
 *         private string $state = "Ok";
 *
 *         public function __construct(#[Getter]private string $value)
 *         {
 *         }
 *     }
 *
 *    $target = new Target("Hello World");
 *
 *    echo $target->getValue(); // "Hello World"
 *    echo $target->getState(); // "Ok"
 */
trait GetterTrait
{
    /**
     * @var array<string, bool>
     */
    private array $getterCache = [];


    /**
     * @param string $method
     * @param array<int, mixed> $args
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $args): mixed
    {
        if (!str_starts_with($method, "get")) {
            throw new BadMethodCallException("$method not considered by GetterTrait::__call.");
        }

        $requestedProp = lcfirst(substr($method, 3));

        if (isset($this->getterCache[$requestedProp])) {
            return $this->$requestedProp;
        }

        $reflectionClass = new ReflectionClass($this);

        $constructor = $reflectionClass->getConstructor();
        if ($constructor) {
            $parameters = $constructor->getParameters();
        }

        $parameters = array_merge($parameters ?? [], $reflectionClass->getProperties());


        foreach ($parameters as $parameter) {
            if ($parameter->getName() !== $requestedProp) {
                continue;
            }

            $attributes = $parameter->getAttributes();

            if ($attributes && $attributes[0]->getName() === Getter::class) {
                $this->getterCache[$requestedProp] = true;
                return $this->$requestedProp;
            }
        }

        throw new BadMethodCallException("$method not found.");
    }
}
