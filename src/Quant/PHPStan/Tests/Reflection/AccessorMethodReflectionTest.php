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

namespace Quant\PHPStan\Tests\Reflection;

use PHPStan\Process\ProcessCrashedException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypehintHelper;
use Quant\PHPStan\Tests\Data\ParentA;
use ReflectionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use Quant\Core\Lang\Modifier;
use Quant\PHPStan\Reflection\AccessorMethodReflection;
use Quant\PHPStan\Reflection\ValueParameter;
use ReflectionClass;

class AccessorMethodReflectionTest extends PHPStanTestCase
{
    /**
     * @throws ShouldNotHappenException
     * @throws MissingPropertyFromReflectionException
     */
    public function testAccessorMethodReflection(): void
    {
        include_once(__DIR__ . "../../data/ParentA.php");

        $tests = [
            [
                "modifier" => Modifier::PUBLIC,
                "isSetter" => true
            ],
            [
                "modifier" => Modifier::PRIVATE,
                "isSetter" => true
            ],
            [
                "modifier" => Modifier::PROTECTED,
                "isSetter" => true
            ],
            [
                "modifier" => Modifier::PUBLIC,
                "isSetter" => false
            ],
            [
                "modifier" => Modifier::PRIVATE,
                "isSetter" => false
            ],
            [
                "modifier" => Modifier::PROTECTED,
                "isSetter" => false
            ]
        ];

        foreach ($tests as $props) {
            $clsName = ParentA::class;
            $declaringClass = $this->getClassReflectionFor($clsName);
            $propertyType   = (new ReflectionClass($clsName))->getProperty("foo")->getType();

            $refl = $this->getReflectionFor($declaringClass, $propertyType, $props["modifier"], $props["isSetter"]);

            $this->assertSame($refl->getDeclaringClass(), $declaringClass);
            $this->assertFalse($refl->isStatic());
            $this->assertSame($props["modifier"] === Modifier::PRIVATE, $refl->isPrivate());
            $this->assertSame($props["modifier"] === Modifier::PUBLIC, $refl->isPublic());
            $this->assertNull($refl->getDocComment());
            $this->assertSame("getValue", $refl->getName());
            $this->assertSame($refl, $refl->getPrototype());
            $this->assertSame(TrinaryLogic::createNo(), $refl->isDeprecated());
            $this->assertNull($refl->getDeprecatedDescription());
            $this->assertSame(TrinaryLogic::createYes(), $refl->isFinal());
            $this->assertSame(TrinaryLogic::createNo(), $refl->isInternal());
            $this->assertNull($refl->getThrowType());

            if ($props["isSetter"]) {
                $this->assertSame(TrinaryLogic::createYes(), $refl->hasSideEffects());
            } else {
                $this->assertSame(TrinaryLogic::createNo(), $refl->hasSideEffects());
            }

            $variants = $refl->getVariants();

            $this->assertSame(1, count($variants));
            $variant = $variants[0];

            $this->assertTrue($variant->getTemplateTypeMap()->isEmpty());
            $this->assertTrue($variant->getResolvedTemplateTypeMap()->isEmpty());
            $this->assertFalse($variant->isVariadic());

            if ($props["isSetter"]) {
                $this->assertSame(1, count($variant->getParameters()));
                $valueParameter = $variant->getParameters()[0];
                $this->assertInstanceOf(ValueParameter::class, $valueParameter);
                $this->assertTrue(
                    TypehintHelper::decideTypeFromReflection($propertyType)->equals($valueParameter->getType())
                );
                $this->assertTrue($variant->getReturnType()->equals(new ObjectType($declaringClass->getName())));
            } else {
                $this->assertSame(0, count($variant->getParameters()));
                $this->assertTrue($variant->getReturnType()->equals(
                    TypehintHelper::decideTypeFromReflection($refl->getPropertyType())
                ));
            }
        }
    }


    /**
     * @throws ShouldNotHappenException
     */
    protected function getReflectionFor(
        ClassReflection $declaringClass,
        ?ReflectionType $propertyType,
        Modifier $modifier,
        bool $isSetter
    ): AccessorMethodReflection {

        if (!$propertyType) {
            throw new ShouldNotHappenException();
        }

        return new AccessorMethodReflection(
            $declaringClass,
            "getValue",
            $propertyType,
            $modifier,
            $isSetter
        );
    }


    protected function getClassReflectionFor(string $className): ClassReflection
    {
        return $this->createReflectionProvider()->getClass($className);
    }
}
