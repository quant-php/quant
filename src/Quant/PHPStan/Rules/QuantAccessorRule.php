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

namespace Quant\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;
use Quant\Core\Lang\Modifier;
use Quant\Core\Trait\AccessorTrait;
use Quant\PHPStan\Reflection\AccessorMethodReflection;
use ReflectionClass;
use ReflectionException;

use function in_array;
use function sprintf;

/**
 * @implements Rule<MethodCall>
 */
class QuantAccessorRule implements Rule
{
    public function __construct(
        private RuleLevelHelper $ruleLevelHelper
    ) {
    }

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }
    public int $i = 0;
    public function processNode(Node $node, Scope $scope): array
    {
        $var = $node->var;
        $methodName = $node->name->name;

        $typeResult = $this->ruleLevelHelper->findTypeToCheck(
            $scope,
            NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $var),
            sprintf(
                'Call to method %s() on an unknown class %%s.',
                SprintfHelper::escapeFormatString($methodName)
            ),
            static fn (Type $type): bool => true
        );
        $type = $typeResult->getType();


        // chained methods
        if ($type instanceof ErrorType) {//} && ($node->var instanceof MethodCall)) {
            $methodCall = $node->var;
            $stack = [];
            while ($methodCall) {
                if (!($methodCall instanceof MethodCall)) {
                    break;
                }
                $stack[] = ["var" => $methodCall->var, "name" => $methodCall->name->name];
                $methodCall = $methodCall->var;
            }

            $stack = array_reverse($stack);

            $rootScope  = null;
            $declaringClass = null;
            foreach ($stack as $methodCall) {
                if (!$rootScope) {
                    $declaringClass = $scope->getType($methodCall["var"])->getObjectClassReflections()[0];
                }

                $methodName = $methodCall["name"];

                if (!$declaringClass) {
                    break;
                }

                $data = $this->queryInheritance($declaringClass, $methodName);

                if (!$data) {
                    return ["error - not found"];
                }

                $methodReflection = $data["methodReflection"];

                if (!$rootScope) {
                    $rootScope = $scope;
                    if (!$rootScope->canCallMethod($methodReflection)) {
                        return [
                            "cannot call from root scope"
                        ];
                    }
                }

                $declaringClass = $methodReflection->getVariants()[0]->getReturnType()->getClassReflection();
                $type = new ObjectType($declaringClass->getName());
            }
        } else {
            $declaringClass = $scope->getType($node->var)->getObjectClassReflections()[0];
        }

        if (!$declaringClass) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Cannot call method %s() on %s.',
                    $methodName,
                    $type->describe(VerbosityLevel::typeOnly()),
                ))->build(),
            ];
        }


        $data = $this->queryInheritance($declaringClass, $methodName);

        if (!$data) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Call to an undefined method %s::%s().',
                    $type->describe(VerbosityLevel::typeOnly()),
                    $methodName,
                ))->build()
            ];
        }

        $declaringClass = $data["declaringClass"];
        $methodReflection = $data["methodReflection"];

        if ($scope->canCallMethod($methodReflection)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Call to %s method %s() of class %s.',
                $methodReflection->isPrivate() ? 'private' : 'protected',
                $methodReflection->getName(),
                $declaringClass->getDisplayName(),
            ))->build()
        ];
    }

    protected function queryInheritance(ClassReflection $declaringClass, string $methodName): false|array
    {
        $cfg = false;
        while ($declaringClass) {
            if (($cfg = $this->hasMethod($declaringClass, $methodName)) !== false) {
                break;
            }
            $declaringClass = $declaringClass->getParentClass();
        }

        if (
            !$cfg ||
            !$declaringClass ||
            !($methodReflection = $this->getMethod($declaringClass, $methodName))
        ) {
            return false;
        }

        return [
            "declaringClass" => $declaringClass,
            "methodReflection" => $methodReflection
        ];
    }


    private function hasMethod(ClassReflection $classReflection, string $methodName): false|array
    {
        $usesTrait = $this->doesClassUseAccessorTrait($classReflection);

        if (!$usesTrait) {
            return false;
        }

        $cfg = $this->getPropertyConfig($classReflection, $methodName);

        return $cfg;
    }

    private function getMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
    {
        $cfg = $this->getPropertyConfig($classReflection, $methodName);

        if (!$cfg) {
            return null;
        }

        return new AccessorMethodReflection(
            $classReflection,
            $methodName,
            $cfg["type"],
            $cfg["modifier"],
            $cfg["isSetter"]
        );
    }

    /**
     * @throws ReflectionException
     */
    private function getPropertyConfig(ClassReflection $classReflection, string $methodName): array|false
    {
        $reflectionClass = new ReflectionClass($classReflection->getName());


        $propName = "";
        $prefix = "get";

        switch (true) {
            case (str_starts_with($methodName, "get")):
                $propName = lcfirst(substr($methodName, 3));
                break;
            case (str_starts_with($methodName, "set")):
                $prefix = "set";
                $propName = lcfirst(substr($methodName, 3));
                break;
            case (str_starts_with($methodName, "is")):
                $prefix = "is";
                $propName = lcfirst(substr($methodName, 2));
                break;
        }

        if (!$propName) {
            return false;
        }

        if ($reflectionClass->hasProperty($propName)) {
            $reflectionProperty = $reflectionClass->getProperty($propName);

            if ($prefix === "is" && $reflectionProperty->getType()->getName() !== "bool") {
                return false;
            }

            if ($prefix === "get" || $prefix === "is") {
                $attributes = $reflectionProperty->getAttributes(Getter::class);

                if (!empty($attributes)) {
                    return [
                        "isSetter" => false,
                        "type"     => $reflectionProperty->getType(),
                        "modifier" => $attributes[0]->getArguments() ?
                            $attributes[0]->getArguments()[0] : Modifier::PUBLIC
                    ];
                }
            }

            if ($prefix === "set") {
                $attributes = $reflectionProperty->getAttributes(Setter::class);

                if (!empty($attributes)) {
                    return [
                        "isSetter" => true,
                        "type"     => $reflectionProperty->getType(),
                        "modifier" => $attributes[0]->getArguments() ?
                            $attributes[0]->getArguments()[0] : Modifier::PUBLIC
                    ];
                }
            }
        }

        return false;
    }

    private function doesClassUseAccessorTrait(ClassReflection $classReflection)
    {
        $parent = $classReflection->getNativeReflection();
        $isTraitUsed = false;
        while ($parent) {
            $traits = $parent->getTraitNames();
            if (in_array(AccessorTrait::class, $traits)) {
                $isTraitUsed = true;
                break;
            }
            $parent = $parent->getParentClass();
        }

        return $isTraitUsed;
    }
}
