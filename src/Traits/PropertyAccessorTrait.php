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
use Quant\Attributes\Setter;
use ReflectionClass;
use ReflectionParameter;
use ValueError;

/**
 * Provides read-/write-access to object properties attributed with `#[Setter]`.
 * For each property (or constructor parameter, e.g. using constructor property promotion) that has such an attribute,
 * a `set[Property_Name]`-method will be available that can be called to set the property.
 * Such `setter`-methods can be guarded with an `apply[Property_Name]`-method: Implementing clients can provide
 * further specifications regarding the domain the property belongs to. The `apply`-method is called by the
 * `set`-method: if such an `apply`-method exists, its return-value will be used as the value for the property whose
 * setter was called.
 *
 * @example
 *
 * ```php
 *    use Setter;
 *    use PropertyAccessorTrait;
 *
 *    class Target {
 *         trait PropertyAccessorTrait;
 *
 *         #[Setter]
 *         public bool $state = true;
 *
 *         public function __construct(
 *              #[Setter]#[Getter]
 *              public string $value,
 *              #[Setter]
 *              bool $state
 *          ) {
 *         {
 *              $this->configureProperties(func_get_args());
 *         }
 *
 *         protected function applyState($value): mixed
 *         {
 *             return true;
 *         }
 *
 *         protected function applyValue($value): mixed
 *         {
 *             return strtoupper($value);
 *         }
 *
 *     }
 *
 *    $target = new Target("Hello World", false);
 *
 *    $target->setValue("Hello World");
 *    echo $target->value; // "HELLO WORLD"
 *    echo $target->getValue(); // "HELLO WORLD"
 *    $target->setState(false);
 *    // the applyState() will take care of always returning true, so $state is never set to false.
 *     echo $target->state; // true
 * ```
 */
trait PropertyAccessorTrait
{
    /**
     * @var array<string, bool>
     */
    private ?array $setterCache = null;

    /**
     * @var array<string, bool>
     */
    private ?array $getterCache = null;


    /**
     * @param string $method
     * @param array<int, mixed> $args
     *
     * @return mixed returns $this for setters, otherwise the value of the property for the property requested
     * via `get`
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $args): mixed
    {
        if (($isSetter = str_starts_with($method, "set")) ||
            str_starts_with($method, "get")) {

            $property = lcfirst(substr($method, 3));

            if ($isSetter) {
                if ($this->hasSetterAttribute($property)) {
                    $this->applyFromSetter($property, $args[0]);
                    return $this;
                }
            } else  {
                if ($this->hasGetterAttribute($property)) {
                    return $this->$property;
                }
            }
        }

        throw new BadMethodCallException("$method not found.");
    }

    private function applyFromSetter(string $property, mixed $value): void
    {
        $applier = "apply" . ucfirst($property);

        $newValue = $value;
        if (method_exists($this, $applier)) {
            $newValue = $this->{$applier}($value);
        }
        $this->$property = $newValue;
    }


    /**
     * Configures the  properties of this class with the values available in $args.
     * The ordinal value of the individual entries in $args is expected to match the ordinal value of the parameter
     * that is to be configured with the value, e.g. to apply a value to parameter $b of the following constructor
     *
     * `__construct($a, $b)`
     *
     * an array in the form of
     *
     * `$args = [1 => "value_of_b"]`
     *
     * Must be passed to this method.
     * This can be automated by calling this method from the constructor with the value of `func_get_args()`:
     *
     * ```php
     *    public function __construct(
     *        private string $a,
     *        #[Setter]
     *        private string $b
     *    ) {
     *        $this->configureProperties(func_get_args());
     *    }
     * ```
     *
     * Note that only those properties will be set that have an Attribute-Annotation `#[Setter]` configured. For
     * these properties, the appropriate `set[Property_Name]()`-method will be called.
     *
     * @param array<int, mixed> $args
     * @return void
     */
    private function configureProperties(array $args): void
    {
        $parameters = $this->getConstructorParameters();

        foreach ($parameters as $index => $parameter) {
            $propertyName = $parameter->getName();

            if (isset($args[$index]) && $this->hasSetterAttribute($propertyName)) {
                $this->__call("set" . ucfirst($parameter->getName()), [$args[$index]]);
            }
        }
    }


    private function hasSetterAttribute(string $propertyName): bool
    {
        if (!$this->setterCache) {
            $this->setterCache = $this->cachePropertiesWithAccessorAttribute(Setter::class);
        }

        return isset($this->setterCache[$propertyName]) && $this->setterCache[$propertyName] === true;
    }

    private function hasGetterAttribute(string $propertyName): bool
    {
        if (!$this->getterCache) {
            $this->getterCache = $this->cachePropertiesWithAccessorAttribute(Getter::class);
        }

        return isset($this->getterCache[$propertyName]) && $this->getterCache[$propertyName] === true;
    }


    /**
     * @return array<string, bool>
     */
    private function cachePropertiesWithAccessorAttribute(string $accessorClass): array
    {
        if (!in_array($accessorClass, [Setter::class, Getter::class])) {
            throw new ValueError("accessorClass must be one of " . Setter::class . " or " . Getter::class);
        }

        $propBag = [];

        $reflectionClass = new ReflectionClass($this);
        $parameters = array_merge(
            $this->getConstructorParameters($reflectionClass),
            $reflectionClass->getProperties()
        );

        foreach ($parameters as $parameter) {
            $propertyName = $parameter->getName();
            $attributes = $parameter->getAttributes();

            if (
                $attributes &&
                count(
                    array_filter($attributes, fn ($attribute) => $attribute->getName() === $accessorClass)
                ) > 0
            ) {
                $propBag[$propertyName] = true;
            }
        }

        return $propBag;
    }


    /**
     * @param ReflectionClass<object>|null $reflectionClass
     * @return array<int, ReflectionParameter>
     */
    private function getConstructorParameters(?ReflectionClass $reflectionClass = null): array
    {
        if (!$reflectionClass) {
            $reflectionClass = new ReflectionClass($this);
        }

        $constructor = $reflectionClass->getConstructor();
        $parameters = [];
        if ($constructor) {
            $parameters = $constructor->getParameters();
        }

        return $parameters;
    }
}
