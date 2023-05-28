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

namespace Quant\Core\Tests;

use ArrayAccess;
use Quant\Core\AbstractList;
use Countable;
use Iterator;
use OutOfBoundsException;
use Quant\Core\Contract\Comparable;
use Quant\Core\Contract\Equatable;
use stdClass;
use PHPUnit\Framework\TestCase;
use TypeError;
use UnhandledMatchError;

class AbstractListTest extends TestCase
{
// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     * @return void
     */
    public function testConstructor(): void
    {

        $abstractList = $this->getAbstractListClass();
        $this->assertSame(stdClass::class, $abstractList->getType());
        $this->assertInstanceOf(Countable::class, $abstractList);
        $this->assertInstanceOf(Equatable::class, $abstractList);
        $this->assertInstanceOf(ArrayAccess::class, $abstractList);
        $this->assertInstanceOf(Iterator::class, $abstractList);
    }

    /**
     * Tests OutOfBoundsException /w string as key
     * @return void
     */
    public function testOffsetSetWithStringAndOutOfBoundsException(): void
    {
        $this->expectException(OutOfBoundsException::class);

        $abstractList = $this->getAbstractListClass();

        /* @phpstan-ignore-next-line */
        $abstractList["1"] = new stdClass();
    }


    /**
     * Tests ArrayAccess /w type exception
     * @return void
     */
    public function testArrayAccessException(): void
    {
        $this->expectException(TypeError::class);

        $abstractList = $this->getAbstractListClass();

        /* @phpstan-ignore-next-line */
        $abstractList[] = "foo";
    }


    /**
     * Tests ArrayAccess
     * @return void
     */
    public function testArrayAccessAndCountable(): void
    {
        $abstractList = $this->getAbstractListClass();

        $cmpList = [
            new stdClass(),
            new stdClass()
        ];

        $abstractList[] = $cmpList[0];
        $abstractList[] = $cmpList[1];

        $this->assertSame(2, count($abstractList));

        foreach ($abstractList as $key => $item) {
            $this->assertSame($cmpList[$key], $item);
        }
    }


    /**
     * Tests Arrayable
     * @return void
     */
    public function testToArray(): void
    {
        $abstractList = $this->getAbstractListClass();

        $cmpList = [
            new stdClass(),
            new stdClass()
        ];

        $abstractList[] = $cmpList[0];
        $abstractList[] = $cmpList[1];

        $this->assertEquals([
            $abstractList[0],
            $abstractList[1]
        ], $abstractList->toArray());
    }


    /**
     * Tests map()
     * @return void
     */
    public function testMap(): void
    {
        $abstractList = $this->getAbstractListClass();

        $cmpList = [
            new stdClass(),
            new stdClass()
        ];

        $cmpList[0]->foo = 1;
        $cmpList[0]->bar = 2;
        $cmpList[1]->foo = 3;
        $cmpList[1]->bar = 4;

        $abstractList[] = $cmpList[0];
        $abstractList[] = $cmpList[1];

        $mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(["mapCallback"])
            ->getMock();


        $matcher = $this->exactly(2);
        $mock->expects($matcher)
            ->method("mapCallback")
            ->willReturnCallback(
                function (stdClass $param) use ($cmpList, $matcher): stdClass {
                    $int = $matcher->numberOfInvocations() - 1;
                    $cmpList[$int]->foo *= 2;
                    return $cmpList[$int];
                }
            );


        /** @phpstan-ignore-next-line */
        $cb = $mock->mapCallback(...);

        $mappedList = $abstractList->map($cb);

        foreach ($mappedList as $index => $item) {
            $this->assertSame($cmpList[$index], $item);
            $this->assertSame([2, 6][$index], $item->foo);
        }
    }


    /**
     * Tests findBy()
     * @return void
     */
    public function testFindBy(): void
    {
        $abstractList = $this->getAbstractListClass();

        $cmpList = [
            new stdClass(),
            new stdClass()
        ];

        $cmpList[0]->foo = 1;
        $cmpList[0]->bar = 2;
        $cmpList[1]->foo = 3;
        $cmpList[1]->bar = 4;

        $abstractList[] = $cmpList[0];
        $abstractList[] = $cmpList[1];

        $mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(["findCallback"])
            ->getMock();

        $matcher = $this->exactly(2);
        $mock->expects($matcher)
            ->method("findCallback")
            ->willReturnCallback(
                fn (stdClass $param): bool => match ($matcher->numberOfInvocations()) {
                    1 => false,
                    2 => true,
                    default => throw new UnhandledMatchError()
                }
            );

        /** @phpstan-ignore-next-line */
        $cb = $mock->findCallback(...);
        $this->assertSame(
            $cmpList[1],
            $abstractList->findBy($cb)
        );
    }

    /**
     * Tests peek()
     * @return void
     */
    public function testPeek(): void
    {
        $abstractList = $this->getAbstractListClass();

        $this->assertNull($abstractList->peek());

        $one = new stdClass();
        $two = new stdClass();

        $abstractList[] = $one;
        $abstractList[] = $two;

        $this->assertSame($two, $abstractList->peek());
    }


    /**
     * Tests make()
     * @return void
     */
    public function testMake(): void
    {
        $abstractList = new class extends AbstractList {
            public function getType(): string
            {
                return stdClass::class;
            }
            public function equals(Equatable $target): bool
            {
                return true;
            }
        };

        $one = new stdClass();
        $two = new stdClass();

        $list = $abstractList::make($one, $two);

        $this->assertInstanceOf($abstractList::class, $list);

        $this->assertSame($list[0], $one);
        $this->assertSame($list[1], $two);
    }


    public function testEquals(): void
    {
        $entityEquatable = new class implements Equatable {
            public int $a = 1;
            public function equals(Equatable $target): bool
            {
                /* @phpstan-ignore-next-line */
                return $this->a === $target->a;
            }
        };

        $entityComparable = new class implements Comparable {
            public int $a = 1;
            public function compareTo(Comparable $target): int
            {
                /* @phpstan-ignore-next-line */
                return $this->a === $target->a ? 0 : -1;
            }
        };

        $equatableList = new class () extends AbstractList {
            public function getType(): string
            {
                return Equatable::class;
            }
        };
        $comparableList = new class () extends AbstractList {
            public function getType(): string
            {
                return Comparable::class;
            }
        };

        // eq
        $eqA1 = new $entityEquatable();
        $eqA2 = new $entityEquatable();
        $eqB1 = new $entityEquatable();
        $eqB2 = new $entityEquatable();

        $listEqA = new $equatableList();
        $listEqB = new $equatableList();
        $this->assertTrue($listEqA->equals($listEqB));

        $listEqA[] = $eqA1;
        $listEqA[] = $eqA2;
        $listEqB[] = $eqB1;
        $this->assertFalse($listEqA->equals($listEqB));
        $listEqB[] = $eqB2;

        $this->assertTrue($listEqA->equals($listEqB));

        $eqB2->a = 0;
        $this->assertFalse($listEqA->equals($listEqB));


        // cmp
        $cmpA1 = new $entityComparable();
        $cmpA2 = new $entityComparable();
        $cmpB1 = new $entityComparable();
        $cmpB2 = new $entityComparable();

        $listCmpA = new $comparableList();
        $listCmpB = new $comparableList();

        $listCmpA[] = $cmpA1;
        $listCmpA[] = $cmpA2;
        $listCmpB[] = $cmpB1;
        $this->assertFalse($listCmpA->equals($listCmpB));
        $listCmpB[] = $cmpB2;

        $this->assertTrue($listCmpA->equals($listCmpB));

        $cmpB2->a = 0;
        $this->assertFalse($listCmpA->equals($listCmpB));
    }



// ---------------------
//    Helper Functions
// ---------------------

    /**
     * @return AbstractList<stdClass>
     */
    protected function getAbstractListClass(): AbstractList
    {
        return new class () extends AbstractList {
            public function getType(): string
            {
                return stdClass::class;
            }
        };
    }
}
