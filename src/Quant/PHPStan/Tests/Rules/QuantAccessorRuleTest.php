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

namespace Quant\PHPStan\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use Quant\PHPStan\Rules\QuantAccessorRule;

class QuantAccessorRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new QuantAccessorRule(
            self::getContainer()->getByType(RuleLevelHelper::class)
        );
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
            ["Call to protected method getProtectedBarC() of class Quant\PHPStan\Tests\Data\C.", 55],
            ["Call to an undefined method Quant\PHPStan\Tests\Data\B::notExisting().", 63],
        ]);
    }
}
