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

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

/**
 * Models a parameter for automated setters, e.g.
 *
 * ```php
 * class A
 * {
 *      #[Setter]
 *      private string $value;
 * }
 * ```
 *
 * The setter-method `setValue` will then accept a `string`-typed argument with the function-parameter `$value`.
 *
 *
 */
class ValueParameter implements ParameterReflection
{
    private Type $type;


    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    public function getName(): string
    {
        return "value";
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function passedByReference(): PassedByReference
    {
        return  PassedByReference::createNo();
    }

    public function isVariadic(): bool
    {
        return false;
    }

    public function getDefaultValue(): ?Type
    {
        return null;
    }
}
