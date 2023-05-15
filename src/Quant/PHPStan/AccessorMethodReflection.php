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
    /** @var ClassReflection */
    private $declaringClass;

    /** @var string */
    private $name;

    /** @var $propertyCfg */
    private $propertyCfg;

    public function __construct(
        ClassReflection $declaringClass,
        string $name,
        array $propertyCfg
    ) {
        $this->declaringClass = $declaringClass;
        $this->name = $name;
        $this->propertyCfg = $propertyCfg;
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
        return $this->propertyCfg["methodModifier"] === Modifier::PRIVATE;
    }

    public function isPublic(): bool
    {
        return $this->propertyCfg["methodModifier"] === Modifier::PUBLIC;
    }

    public function isProtected(): bool
    {
        return $this->propertyCfg["methodModifier"] === Modifier::PROTECTED;
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
        $type = $this->propertyCfg["type"];
        $isSetter = $this->propertyCfg["prefix"] === "set";
        $isGetter = $this->propertyCfg["prefix"] === "get";

        $writeableType = TypehintHelper::decideTypeFromReflection($type);
        $returnType = $isSetter ?  new ObjectType($this->propertyCfg["declaringClass"]) : $writeableType;


        $arguments = [];

        if ($isSetter) {
            $arguments = [
                new DummyParameter('value', $writeableType)
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
        return $this->propertyCfg["prefix"] === "set" ? TrinaryLogic::createYes() : TrinaryLogic::createNo();
    }
}
