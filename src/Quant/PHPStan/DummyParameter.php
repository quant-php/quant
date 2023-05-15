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

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

class DummyParameter implements ParameterReflection
{
    /** @var string */
    private $name;

    /** @var Type */
    private $type;

    /** @var bool */
    private $optional;

    /** @var PassedByReference */
    private $passedByReference;

    /** @var bool */
    private $variadic;

    /** @var Type|null */
    private $defaultValue;

    public function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
        $this->optional = false;
        $this->passedByReference = PassedByReference::createNo();
        $this->variadic = false;
        $this->defaultValue = null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function passedByReference(): PassedByReference
    {
        return $this->passedByReference;
    }

    public function isVariadic(): bool
    {
        return $this->variadic;
    }

    public function getDefaultValue(): ?Type
    {
        return $this->defaultValue;
    }
}
