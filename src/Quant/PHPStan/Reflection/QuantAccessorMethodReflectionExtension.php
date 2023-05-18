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

use PHPStan\BetterReflection\Reflection\ReflectionNamedType;
use ReflectionType;
use PHPStan\BetterReflection\Reflection\ReflectionUnionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
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
 * Owning classes must use a AccessorTrait, or inherit from a class that uses an AccessorTrait, in order for
 * getters/setters to be properly resolved-.
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

        return !!$this->queryMethod($classReflection, $methodName);
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $methodReflection = $this->queryMethod($classReflection, $methodName);

        if (!$methodReflection) {
            throw new ShouldNotHappenException();
        }

        return $methodReflection;
    }


    protected function queryMethod(ClassReflection $declaringClass, string $methodName): ?MethodReflection
    {
        $methodReflection = null;
        while ($declaringClass) {
            if (($methodReflection = $this->resolveMethod($declaringClass, $methodName))) {
                break;
            }
            $declaringClass = $declaringClass->getParentClass();
        }

        return $methodReflection;
    }


    public function resolveMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
    {
        $cfg = $this->getPropertyConfig($classReflection, $methodName);

        if (!$cfg) {
            return null;
        }

        return new AccessorMethodReflection($classReflection, $methodName, ...$cfg);
    }


    /**
     * @throws ReflectionException
     *
     * @return null|array{isSetter: bool, propertyType: ReflectionType|null, modifier: Modifier}
     */
    protected function getPropertyConfig(ClassReflection $classReflection, string $methodName): ?array
    {
        $reflectionClass = new ReflectionClass($classReflection->getName());

        $propName = lcfirst(substr($methodName, 3));
        $prefix = "get";

        switch (true) {
            case (str_starts_with($methodName, "set")):
                $prefix = "set";
                // no break intentional
            case (str_starts_with($methodName, "get")):
                break;
            case (str_starts_with($methodName, "is")):
                $prefix = "is";
                $propName = lcfirst(substr($methodName, 2));
                break;
            default:
                $propName = null;
                break;
        }

        if (!$propName) {
            return null;
        }

        $classAttributes = $reflectionClass->getAttributes($prefix === "set" ? Setter::class : Getter::class);

        if ($reflectionClass->hasProperty($propName)) {
            $reflectionProperty = $reflectionClass->getProperty($propName);

            /* @phpstan-ignore-next-line */
            if ($prefix === "is" && $reflectionProperty->getType()?->getName() !== "bool") {
                return null;
            }

            if (empty($classAttributes)) {
                $attributes = $reflectionProperty->getAttributes($prefix === "set" ? Setter::class : Getter::class);
            } else {
                $attributes = $classAttributes;
            }

            if (empty($attributes)) {
                return null;
            }

            $modArgs = $attributes[0]->getArguments();
            $modifier = $modArgs ?
                ($modArgs[0] instanceof Modifier ? $modArgs[0] : Modifier::PUBLIC) : Modifier::PUBLIC;

            return [
                "isSetter"     => !($prefix === "get" || $prefix === "is"),
                "propertyType" => $reflectionProperty->getType(),
                "modifier"     => $modifier
            ];
        }

        return null;
    }


    private function doesClassUseAccessorTrait(ClassReflection $classReflection): bool
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
