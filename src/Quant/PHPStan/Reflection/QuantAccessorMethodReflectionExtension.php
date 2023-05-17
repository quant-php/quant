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

namespace Quant\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use Quant\Core\Lang\Modifier;
use Quant\Core\Trait\AccessorTrait;
use ReflectionClass;
use ReflectionException;

/**
 * Extension for resolving to classes where #[Setter] / #[Getter] is either used
 *  - on class level
 *  - on class property level
 *  - on constructor parameter level (constructor property promotion)
 *
 * Owning classes must use a AccessorTrait, or inherit from a class that uses an AccessorTrait
 *
 *
 */
class QuantAccessorMethodReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $usesTrait = $this->doesClassUseAccessorTrait($classReflection);

        if (!$usesTrait) {
            return false;
        }

        return !!$this->queryInheritance($classReflection, $methodName);
    }

    protected function queryInheritance(ClassReflection $declaringClass, string $methodName): false|array
    {
        $methodReflection = false;
        while ($declaringClass) {
            if (($methodReflection = $this->resolveMethod($declaringClass, $methodName))) {
                break;
            }
            $declaringClass = $declaringClass->getParentClass();
        }

        if (
            !$methodReflection ||
            !$declaringClass
        ) {
            return false;
        }

        return [
            "declaringClass" => $declaringClass,
            "methodReflection" => $methodReflection
        ];
    }


    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $data = $this->queryInheritance($classReflection, $methodName);

        return $data["methodReflection"];
    }

    public function resolveMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
    {
        $cfg = $this->getPropertyConfig($classReflection, $methodName);

        if (!$cfg) {
            return null;
        }

        return new AccessorMethodReflection(
            $classReflection,
            $methodName,
            $cfg["type"],
            $cfg["modifier"],
            $cfg["isSetter"]
        );
    }

    /**
     * @throws ReflectionException
     */
    protected function getPropertyConfig(ClassReflection $classReflection, string $methodName): array|false
    {
        $reflectionClass = new ReflectionClass($classReflection->getName());


        $propName = "";
        $prefix = "get";

        switch (true) {
            case (str_starts_with($methodName, "get")):
                $prefix = "get";
                $propName = lcfirst(substr($methodName, 3));
                break;
            case (str_starts_with($methodName, "set")):
                $prefix = "set";
                $propName = lcfirst(substr($methodName, 3));
                break;
            case (str_starts_with($methodName, "is")):
                $prefix = "is";
                $propName = lcfirst(substr($methodName, 2));
                break;
        }

        if (!$propName) {
            return false;
        }

        if ($reflectionClass->hasProperty($propName)) {
            $reflectionProperty = $reflectionClass->getProperty($propName);

            if ($prefix === "is" && $reflectionProperty->getType()->getName() !== "bool") {
                return false;
            }

            if ($prefix === "get" || $prefix === "is") {
                $attributes = $reflectionProperty->getAttributes(Getter::class);

                if (!empty($attributes)) {
                    return [
                        "isSetter" => false,
                        "type"     => $reflectionProperty->getType(),
                        "modifier" => $attributes[0]->getArguments() ? $attributes[0]->getArguments()[0] : Modifier::PUBLIC
                    ];
                }
            }

            if ($prefix === "set") {
                $attributes = $reflectionProperty->getAttributes(Setter::class);

                if (!empty($attributes)) {
                    return [
                        "isSetter" => true,
                        "type"     => $reflectionProperty->getType(),
                        "modifier" => $attributes[0]->getArguments() ? $attributes[0]->getArguments()[0] : Modifier::PUBLIC
                    ];
                }
            }
        }

        return false;
    }


    protected function doesClassUseAccessorTrait(ClassReflection $classReflection)
    {
        $parent = $classReflection->getNativeReflection();
        $isTraitUsed = false;
        while ($parent) {
            $traits = $parent->getTraitNames();
            if (in_array(AccessorTrait::class, $traits)) {
                $isTraitUsed = true;
                break;
            }
            $parent = $parent->getParentClass();
        }

        return $isTraitUsed;
    }
}
