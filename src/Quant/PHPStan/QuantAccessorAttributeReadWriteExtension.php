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

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use ReflectionException;

/**
 * #[Setter] / #[Getter] attributed properties  are given a setter / getter, thus we assume the properties
 * are always written / read.
 */
class QuantAccessorAttributeReadWriteExtension implements ReadWritePropertiesExtension
{
    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        return $this->isQuantAttributed($property, $propertyName);
    }


    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        return $this->isQuantAttributed($property, $propertyName);
    }


    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }


    /**
     * @throws ReflectionException
     */
    protected function isQuantAttributed(PropertyReflection $property, string $propertyName)
    {
        $reflectionClass = $property->getDeclaringClass()->getNativeReflection();
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $attributes = $reflectionProperty->getAttributes();

        foreach ($attributes as $attribute) {
            if (in_array($attribute->getName(), [Setter::class, Getter::class])) {
                return true;
            }
        }

        return false;
    }
}
