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

use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallMethodsRule>
 */
class QuantAccessorMethodReflectionExtensionTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(CallMethodsRule::class);
    }


    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/../../extension.neon']
        );
    }


    public function testRule(): void
    {
        $this->analyse([__DIR__ . "../../Data/B.php"], [
            ["Call to private method setPrivateBar() of class Quant\PHPStan\Tests\Data\A.", 32],
            ["Call to private method getPrivateBar() of class Quant\PHPStan\Tests\Data\A.", 37],
            ["Cannot call method getProtectedBar() on string.", 54],
            ["Call to protected method getProtectedBarC() of class Quant\PHPStan\Tests\Data\C.", 57],
            ["Call to an undefined method Quant\PHPStan\Tests\Data\B::notExisting().", 65],
            ["Call to an undefined method Quant\PHPStan\Tests\Data\A::notExisting().", 69]
        ]);

        $this->analyse([__DIR__ . "../../Data/D.php"], [
        ]);

        $this->analyse([__DIR__ . "../../Data/ChildD.php"], [
        ]);
    }
}
