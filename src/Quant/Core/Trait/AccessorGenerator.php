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

namespace Quant\Core\Trait;

use BadMethodCallException;
use Quant\Core\Lang\Modifier;
use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use TypeError;
use ValueError;

/**
 * Provides read-/write-access to object properties attributed with `#[Setter]`.
 * For each property (or constructor parameter, e.g. using constructor property promotion) that has such an attribute,
 * a `set[Property_Name]`-method will be available that can be called to set the property.
 * Such `setter`-methods can be guarded with an `apply[Property_Name]`-method: Implementing clients can provide
 * further specifications regarding the domain the property belongs to. The `apply`-method is called by the
 * `set`-method: if such an `apply`-method exists, its return-value will be used as the value for the property whose
 * setter was called. The return of the setter will be *this* instance.
 * Getters in turn can be installed using the #[Getter] attribute.
 *
 * If the class itself is attributed with #[Getter] or #[Setter], for all properties of the class individual getters
 * and setters will be made available.
 *
 * @example
 *
 * ```php
 *    use Setter;
 *    use AccessorGenerator;
 *    use const Quant\Core\Constants\ACCESS_LEVEL_PROTECTED as ACCESS_PROTECTED;
 *    use const Quant\Core\Constants\ACCESS_LEVEL_PUBLIC as ACCESS_PUBLIC;
 *
 *    class Target {
 *         trait AccessorGenerator;
 *
 *         #[Setter]
 *         public bool $state = true;
 *
 *         #[Getter(accessLevel: PROTECTED_ACCESS)]
 *         private string $protectedProperty = "access protected";
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
 *    try {
 *       $target->setState(false);
 *    } catch (ValueError $err) {
 *      echo "Cannot set the value for state: ". $err->getMessage();
 *      die();
 *   }
 *
 *    // the applyState() will take care of always returning true, so $state is never set to false.
 *     echo $target->state; // true
 * ```
 */
trait AccessorGenerator
{
    private const GET = "get";
    private const SET = "set";

    /**
     * @var array<string, array>
     */
    private ?array $setterCache = null;

    /**
     * @var array<string, array>
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
        if (
            ($isSetter = str_starts_with($method, self::SET)) ||
            str_starts_with($method, self::GET)
        ) {
            $property = lcfirst(substr($method, 3));

            if ($isSetter) {
                if ($this->isCallable(self::SET, $property)) {
                    $this->applyFromSetter($property, $args[0]);
                    return $this;
                }
            } else {
                if ($this->isCallable(self::GET, $property)) {
                    return $this->$property;
                }
            }
        }

        throw new BadMethodCallException("$method not found.");
    }


    protected function isCallable(string $accessType, string $property): bool
    {
        $attributeCfg = $accessType === self::GET
            ? $this->hasGetterAttribute($property)
            : $this->hasSetterAttribute($property);

        if ($attributeCfg === false) {
            return false;
        }

        if (count($attributeCfg) && $attributeCfg[0] === Modifier::PROTECTED) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            if ($bt[2]["class"] !== get_class() && !is_subclass_of($this, $bt[2]["class"], true)) {
                return false;
            }
        }

        return true;
    }



    /**
     * @param string $property
     * @param mixed $value
     * @return void
     *
     * @throws ValueError
     * @throws TypeError
     */
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
                $this->__call(self::SET . ucfirst($parameter->getName()), [$args[$index]]);
            }
        }
    }


    private function hasSetterAttribute(string $propertyName): array|false
    {
        if (!$this->setterCache) {
            $this->setterCache = $this->cachePropertiesWithAccessorAttribute(Setter::class);
        }

        return $this->setterCache[$propertyName] ?? false;
    }

    private function hasGetterAttribute(string $propertyName): array|false
    {
        if (!$this->getterCache) {
            $this->getterCache = $this->cachePropertiesWithAccessorAttribute(Getter::class);
        }

        return $this->getterCache[$propertyName] ?? false;
    }


    /**
     * @return array<string, array>
     */
    private function cachePropertiesWithAccessorAttribute(string $accessorClass): array
    {
        if (!in_array($accessorClass, [Setter::class, Getter::class])) {
            throw new ValueError("accessorClass must be one of " . Setter::class . " or " . Getter::class);
        }

        $propBag = [];

        $reflectionClass = new ReflectionClass($this);
        $classAccessorAttribute = $reflectionClass->getAttributes($accessorClass);

        $properties = $this->harvestProperties($reflectionClass);

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            if (in_array($propertyName, ["getterCache", "setterCache"])) {
                continue;
            }

            $accessorAttribute = $classAccessorAttribute;

            if (empty($accessorAttribute)) {
                $accessorAttribute = $property->getAttributes($accessorClass);
            }

            if (!empty($accessorAttribute)) {
                $propBag[$propertyName] = $accessorAttribute[0]->getArguments() ?? [];
            }
        }

        return $propBag;
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return array<int, ReflectionParameter|ReflectionProperty>
     */
    private function harvestProperties(ReflectionClass $reflectionClass): array
    {
        $properties = array_merge(
            $this->getConstructorParameters($reflectionClass),
            $reflectionClass->getProperties()
        );

        // parent
        $parent = $reflectionClass->getParentClass();
        while ($parent) {
            $properties = array_merge(
                $properties,
                $this->getConstructorParameters($parent),
                $parent->getProperties()
            );
            $parent = $parent->getParentClass();
        }

        return $properties;
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
