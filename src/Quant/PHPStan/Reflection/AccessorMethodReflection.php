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

use PHPStan\Type\IntegerType;
use ReflectionType;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use Quant\Core\Lang\Modifier;

class AccessorMethodReflection implements MethodReflection
{
    public function __construct(
        private ClassReflection $declaringClass,
        private string $name,
        private ReflectionType $propertyType,
        private Modifier $modifier,
        private bool $isSetter
    ) {
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return ($this->modifier === Modifier::PRIVATE);
    }


    public function isPublic(): bool
    {
        return ($this->modifier === Modifier::PUBLIC);
    }


    public function getDocComment(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function getVariants(): array
    {
        $writeableType = TypehintHelper::decideTypeFromReflection($this->propertyType);
        $returnType = $this->isSetter ? new ObjectType($this->declaringClass->getName()) : $writeableType;

        $arguments = [];

        if ($this->isSetter) {
            $arguments = [
                new ValueParameter($writeableType)
            ];
        }

        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                null,
                $arguments,
                false,
                $returnType
            ),
        ];
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return $this->isSetter ? TrinaryLogic::createYes() : TrinaryLogic::createNo();
    }
}
