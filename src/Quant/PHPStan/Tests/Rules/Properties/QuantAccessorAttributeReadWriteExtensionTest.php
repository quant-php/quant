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

namespace Quant\PHPStan\Tests\Rules\Properties;

use PHPStan\Rules\DeadCode\UnusedPrivatePropertyRule;
use PHPStan\Rules\Properties\DirectReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Quant\PHPStan\Rules\Properties\QuantAccessorAttributeReadWriteExtension;

class QuantAccessorAttributeReadWriteExtensionTest extends RuleTestCase
{
    public function testQuantAccessorAttributeReadWriteExtension(): void
    {
        $extension = new QuantAccessorAttributeReadWriteExtension();
        $this->assertInstanceOf(ReadWritePropertiesExtension::class, $extension);
    }


    public function testRule(): void
    {
        $tip = 'See: https://phpstan.org/developing-extensions/always-read-written-properties';

        $this->analyse([__DIR__ . "../../../Data/A.php"], [
            [
                "Property Quant\PHPStan\Tests\Data\A::\$neverRead is never read, only written.",
                38,
                $tip
            ],
            [
                "Property Quant\PHPStan\Tests\Data\A::\$unused is unused.",
                40,
                $tip
            ]
        ]);
    }

    protected function getRule(): Rule
    {
        return new UnusedPrivatePropertyRule(
            new DirectReadWritePropertiesExtensionProvider([
                new QuantAccessorAttributeReadWriteExtension()
            ]),
            [],
            [],
            true,
        );
    }
}
