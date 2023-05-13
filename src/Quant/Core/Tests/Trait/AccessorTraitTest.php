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

namespace Quant\Core\Tests\Trait;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Quant\Core\Tests\Trait\Resources\A;
use Quant\Core\Tests\Trait\Resources\B;
use Quant\Core\Tests\Trait\Resources\C;
use Quant\Core\Tests\Trait\Resources\ClassHasAttributes;
use ValueError;

class AccessorTraitTest extends TestCase
{
    public function testA(): void
    {

        $args_1 = [
            "foo" => "Hello World"
        ];
        $args_2 = [
            "foo" => "Hello World!"
        ];

        $inst = $this->createA($args_1);
        $anotherInst = $this->createA($args_2);

        $this->assertSame($inst, $inst->setFoo("foo"));

        $this->assertSame($inst, $inst->setFoobar("Ok"));
        $this->assertSame("Ok", $inst->foobar);

        $this->assertSame($anotherInst, $anotherInst->setFoo("foo"));
        $this->assertSame("foo", $anotherInst->getFoo());
        $this->assertSame("foo", $inst->getFoo());
        $this->assertSame("foo", $inst->getFoo());

        // guarded
        $this->assertSame($anotherInst, $anotherInst->setFoo("noset"));
        $this->assertSame("foo", $anotherInst->getFoo());

        $this->assertTrue($inst->isBool());
        $this->assertSame("true", $inst->getNotBool());

        try {
            /* @phpstan-ignore-next-line */
            $inst->isNotBool();
            $this->fail("Exception excepted");
        } catch (BadMethodCallException$e) {
        }
    }


    public function testSettersAndGettersForClass(): void
    {
        $inst = $this->createClassWithSetterAndGetterAttributes([
            "foo" => "Hello World",
            "bar" => "World Hello"
        ]);


        $inst->setFoo("foo")->setBar("bar")->setSnafu("snafu")->setFoobar("foobar");

        $this->assertSame("foo", $inst->getFoo());
        $this->assertSame("bar", $inst->getBar());
        $this->assertSame("snafu", $inst->getSnafu());
        $this->assertSame("foobar", $inst->getFoobar());
    }


    public function testAccessorTraitWithDifferentValues(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage(">= 2");

        $inst = $this->createA();

        $this->assertSame($inst, $inst->setValueErrorTrigger(1));
    }

    public function testGetProtectedPropertyOnA(): void
    {
        $this->expectException(BadMethodCallException::class);

        $inst = $this->createA();
        $inst->getProtectedVar();
    }

    public function testSetProtectedPropertyOnA(): void
    {
        $this->expectException(BadMethodCallException::class);
        $inst = $this->createA();
        $inst->setProtectedVar("foo");
    }

    public function testBInstance(): void
    {
        $args_1 = [
            "foo" => "Hello World"
        ];

        $inst = $this->createB($args_1);
        $this->assertSame("protected", $inst->proxyProtectedVar());

        $this->assertSame("Hello World", $inst->getFoo());

        $inst->setFoobar("oofrab");
        $this->assertSame("oofrab", $inst->getFoobar());

        $inst->setPrivateVar(123);
        $this->assertSame(123, $inst->getPrivateVar());

        $this->assertFalse($inst->proxyIsValid());
        $this->assertSame($inst, $inst->proxySetValid(true));
        $this->assertTrue($inst->proxyIsValid());
    }

    public function testC(): void
    {
        $args_1 = [
            "foo" => "Hello World"
        ];

        $inst = $this->createC($args_1);

        $inst->setPrivateVar(123);
        $this->assertSame(123, $inst->getPrivateVar());

        $this->assertNotSame("updated", $inst->proxyProtectedVar());
        $inst->setProxyProtectedVar("updated");
        $this->assertSame("updated", $inst->proxyProtectedVar());

        $this->assertNotSame("new value", $inst->getGuarded());
        $inst->setGuarded("new value");
        $this->assertSame("new value", $inst->getGuarded());

        $this->assertNotSame("new value", $inst->getProtectedGuard());
        $inst->setProtectedGuard("will be ignored");
        $this->assertSame("protected guard", $inst->getProtectedGuard());
    }


    public function testProtectedPropertyWithProxy(): void
    {
        $inst = $this->createA();
        $this->assertSame("protected", $inst->proxyProtectedVar());
    }


    public function testBadMethodCallExceptionOnSetNotExisting(): void
    {
        $this->expectException(BadMethodCallException::class);
        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->setBar("missing");
    }


    public function testBadMethodCallExceptionOnSetNotAttributed(): void
    {
        $this->expectException(BadMethodCallException::class);

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->setSnafu(true);
    }


    public function testBadMethodCallExceptionOnNotSetPrefixed(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("not found");

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->snafu();
    }

    public function testBadMethodCallExceptionOnGetNotExisting(): void
    {
        $this->expectException(BadMethodCallException::class);

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->getBar();
    }


    public function testBadMethodCallExceptionOnGetNotAttributed(): void
    {
        $this->expectException(BadMethodCallException::class);

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->getSnafu();
    }


    public function testBadMethodCallExceptionOnNotGetPrefixed(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("not found");

        $inst = $this->createA();

        /* @phpstan-ignore-next-line*/
        $inst->snafu();
    }


    /**
     * @param array<string, string> $data
     *
     * @return A
     */
    protected function createA(array $data = ["foo" => "bar"]): object
    {
        return new A(...$data);
    }

    /**
     * @param array<string, string> $data
     *
     * @return B
     */
    protected function createB(array $data): object
    {
        return new B(...$data);
    }

    /**
     * @param array<string, string> $data
     *
     * @return C
     */
    protected function createC(array $data): object
    {
        return new C(...$data);
    }


    /**
     * @param array<string, string> $data
     *
     * @return ClassHasAttributes
     */
    protected function createClassWithSetterAndGetterAttributes(array $data): object
    {
        return new ClassHasAttributes(...$data);
    }
}
