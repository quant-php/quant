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

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\DeadCode\UnusedPrivatePropertyRule;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Methods\MethodCallCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\DirectReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use Quant\PHPStan\Rules\Properties\QuantAccessorAttributeReadWriteExtension;

class QuantAccessorMethodReflectionExtensionTest extends RuleTestCase
{
    private bool $checkThisOnly;

    private bool $checkNullables;

    private bool $checkUnionTypes;

    private bool $checkExplicitMixed = false;

    private bool $checkImplicitMixed = false;

    private int $phpVersion = PHP_VERSION_ID;
    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();
        $ruleLevelHelper = new RuleLevelHelper(
            $reflectionProvider,
            $this->checkNullables,
            $this->checkThisOnly,
            $this->checkUnionTypes,
            $this->checkExplicitMixed,
            $this->checkImplicitMixed,
            true,
            false
        );
        return new CallMethodsRule(
            new MethodCallCheck($reflectionProvider, $ruleLevelHelper, true, true),
            new FunctionCallParametersCheck($ruleLevelHelper, new NullsafeCheck(), new PhpVersion($this->phpVersion), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), true, true, true, true, true),
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
        $this->checkThisOnly = false;
        $this->checkNullables = true;
        $this->checkUnionTypes = true;

        $this->analyse([__DIR__ . "../../Data/B.php"], [

        ]);
    }
}
