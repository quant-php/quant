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

namespace Quant\PHPStan;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use Quant\Core\Lang\Modifier;
use ReflectionClass;

use function Quant\Core\PHPStan\str_starts_with;

class QuantAccessorMethodReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $cfg = $this->getPropertyConfig($classReflection, $methodName);

        return !!$cfg;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $cfg = $this->getPropertyConfig($classReflection, $methodName);
        return new AccessorMethodReflection($classReflection, $methodName, $cfg);
    }


    /**
     * @throws \ReflectionException
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
                        "declaringClass"   => $reflectionClass->getName(),
                        "prefix"           => $prefix,
                        "type"             => $reflectionProperty->getType(),
                        "attribute"        => Getter::class,
                        "methodModifier"   => $attributes[0]->getArguments()
                            ? $attributes[0]->getArguments()[0] : Modifier::PUBLIC,
                        "propertyModifier" => $reflectionProperty->getModifiers()
                    ];
                }
            }

            if ($prefix === "set") {
                $attributes = $reflectionProperty->getAttributes(Setter::class);

                if (!empty($attributes)) {
                    return [
                        "declaringClass"   => $reflectionClass->getName(),
                        "prefix"           => $prefix,
                        "type"             => $reflectionProperty->getType(),
                        "attribute"        => Setter::class,
                        "methodModifier"   => $attributes[0]->getArguments()
                            ? $attributes[0]->getArguments()[0] : Modifier::PUBLIC,
                        "propertyModifier" => $reflectionProperty->getModifiers()
                    ];
                }
            }
        }

        return false;
    }
}
