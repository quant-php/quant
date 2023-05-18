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

namespace Quant\Core\Tests\Benchmarks\Resources\DocAnnotations;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionException;

/**
 *
 */
class A
{
    /**
     * @Getter
     */
    private string $aPublic = "aPublic";

    private array $getterCache = [];

    /**
     * @throws ReflectionException
     */
    public function __call($method, $args)
    {
        if ($this->getAccessor($method)) {
            return $this->{$method};
        }
    }


    /**
     * @throws ReflectionException
     */
    private function getAccessor(string $forProperty)
    {
        if (isset($this->getterCache[$forProperty])) {
            return $this->getterCache[$forProperty];
        }

        $reflClass = new ReflectionClass($this);
        $reader = new AnnotationReader();
        $property = $reflClass->getProperty($forProperty);

        $this->getterCache[$forProperty] = $reader->getPropertyAnnotation(
            $property,
            Getter::class
        );

        return $this->getterCache[$forProperty];
    }
}
