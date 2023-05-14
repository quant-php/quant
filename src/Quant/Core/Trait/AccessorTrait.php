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

trait AccessorTrait
{
    private const GET = "get";
    private const SET = "set";

    private const IS = "is";

    /**
     * @var array<string, array<string, array<int, int>|string>>
     */
    private ?array $setterCache = null;

    /**
     * @var array<string, array<string, array<int, int>|string>>
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
        $isSetter = $isBooleanGetter = false;

        if (
            ($isGetter        = str_starts_with($method, self::GET)) ||
            ($isBooleanGetter = str_starts_with($method, self::IS))  ||
            ($isSetter        = str_starts_with($method, self::SET))
        ) {
            $property = lcfirst(substr($method, ($isSetter || $isGetter) ? 3 : 2));

            if ($isSetter) {
                if (($propertyCfg = $this->isCallable(self::SET, $property)) !== false) {
                    $this->applyFromSetter($property, $args[0], $propertyCfg);
                    return $this;
                }
            } else {
                if (($propertyCfg = $this->isCallable($isBooleanGetter ? self::IS : self::GET, $property))) {

                    /**
                     * @var string $decl
                     */
                    $decl =  $propertyCfg["decl"];
                    $fn = \Closure::bind(fn ($property) => $this->{$property}, $this, $decl);
                    return $fn($property);
                }
            }
        }

        throw new BadMethodCallException("$method not found.");
    }


    /**
     * @param string $accessType
     * @param string $property
     * @return false|array<string, array<int, int>|string>
     */
    private function isCallable(string $accessType, string $property): false|array
    {
        $propertyCfg = $accessType === self::GET || $accessType === self::IS
            ? $this->hasGetterAttribute($property)
            : $this->hasSetterAttribute($property);

        if ($propertyCfg === false) {
            return false;
        }

        // property must be declared in a parent class of **this**
        if (!($this instanceof $propertyCfg["decl"])) {
            return false;
        }

        $type = $propertyCfg["type"];
        if (
            $type === "bool" && $accessType === self::GET ||
            $type !== "bool" && $accessType === self::IS
        ) {
            return false;
        }

        $argCfg = $propertyCfg["args"];

        if ($argCfg && ($argCfg[0] === Modifier::PROTECTED || $argCfg[0] === Modifier::PRIVATE)) {
            $accessLevel = $propertyCfg["args"][0];

            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

            if (
                /* @phpstan-ignore-next-line */
                $accessLevel === Modifier::PROTECTED &&
                /* @phpstan-ignore-next-line */
                (!($this instanceof $bt[2]["class"])) //is_subclass_of($this, $bt[2]["class"], true)
            ) {
                return false;
            }
            if (
                /* @phpstan-ignore-next-line */
                $accessLevel === Modifier::PRIVATE &&
                /* @phpstan-ignore-next-line */
                $bt[2]["class"] !== $propertyCfg["decl"]
            ) {
                return false;
            }
        }

        return $propertyCfg;
    }


    /**
     * @param string $property
     * @param mixed $value
     * @param array<string, array<int, int>|string> $propertyCfg
     * @return void
     *
     */
    private function applyFromSetter(string $property, mixed $value, array $propertyCfg): void
    {
        $applier = "apply" . ucfirst($property);
        /**
         * @var string $declaringClass
         */
        $declaringClass = $propertyCfg["decl"];
        $newValue = $value;

        if (method_exists($this, $applier) && is_callable($applier)) {
            $newValue = $this->{$applier}($value);
        } elseif (method_exists($declaringClass, $applier)) {
            // if the applier was not found, it is possible that it was declared
            // as private in the property's declaring class
            $fn = \Closure::bind(fn ($value) => $this->{$applier}($value), $this, $declaringClass);
            $newValue = $fn($newValue);
        }

        // $propertyCfg["property"]->setValue($this, $newValue);
        $fn = \Closure::bind(fn ($newValue) => $this->{$property} = $newValue, $this, $declaringClass);
        $fn($newValue);
    }


    /**
     * @param array<int, mixed> $args
     */
    private function applyProperties(array $args): void
    {
        $parameters = $this->getConstructorParameters();

        foreach ($parameters as $index => $parameter) {
            $propertyName = $parameter->getName();

            if (isset($args[$index]) && $this->hasSetterAttribute($propertyName)) {
                $this->__call(self::SET . ucfirst($parameter->getName()), [$args[$index]]);
            }
        }
    }


    /**
     * @param string $propertyName
     * @return array<string, array<int, int>|string>|false
     */
    private function hasSetterAttribute(string $propertyName): array|false
    {
        if ($this->setterCache === null) {
            $this->setterCache = $this->cachePropertiesWithAccessorAttribute(Setter::class);
        }

        return $this->setterCache[$propertyName] ?? false;
    }


    /**
     * @param string $propertyName
     * @return array<string, array<int, int>|string>|false
     */
    private function hasGetterAttribute(string $propertyName): array|false
    {
        if ($this->getterCache === null) {
            $this->getterCache = $this->cachePropertiesWithAccessorAttribute(Getter::class);
        }

        return $this->getterCache[$propertyName] ?? false;
    }


    /**
     * @return array<string, array<string, array<int, int>|string>>
     */
    private function cachePropertiesWithAccessorAttribute(string $accessorClass): array
    {
        $propBag = [];

        $reflectionClass = new ReflectionClass($this);
        $classAccessorAttribute = $reflectionClass->getAttributes($accessorClass);

        $properties = $this->harvestProperties($reflectionClass);

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            if ($propertyName === "getterCache" || $propertyName === "setterCache") {
                continue;
            }

            $accessorAttribute = $classAccessorAttribute;

            if (empty($accessorAttribute)) {
                $accessorAttribute = $property->getAttributes($accessorClass);
            }

            if (!empty($accessorAttribute) && ($property instanceof ReflectionProperty)) {
                $propBag[$propertyName] = [
                    "args" => $accessorAttribute[0]->getArguments() ?: [],
                    /*__toString vs getName */
                    /* @phpstan-ignore-next-line */
                    "type" => $property->getType()?->getName(),
                    "decl" => $property->getDeclaringClass()->getName()
                ];
            }
        }

        return $propBag;
    }

    /**
     * @param ReflectionClass<Object> $reflectionClass
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

        return $reflectionClass->getConstructor() ? $constructor->getParameters() : [];
    }
}
