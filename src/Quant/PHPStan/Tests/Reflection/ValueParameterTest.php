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

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\StringType;
use PHPUnit\Framework\TestCase;
use Quant\PHPStan\Reflection\ValueParameter;

class ValueParameterTest extends TestCase
{
    public function testValueParameter(): void
    {
        $type = new StringType();

        $parameter = new ValueParameter($type);

        $this->assertInstanceOf(ParameterReflection::class, $parameter);

        $this->assertSame("value", $parameter->getName());
        $this->assertFalse($parameter->isOptional());
        $this->assertSame($type, $parameter->getType());
        $this->assertSame(PassedByReference::createNo(), $parameter->passedByReference());
        $this->assertFalse($parameter->isVariadic());
        $this->assertNull($parameter->getDefaultValue());
    }
}
