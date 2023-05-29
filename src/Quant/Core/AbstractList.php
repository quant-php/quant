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

namespace Quant\Core;

use ArrayAccess;
use Quant\Core\Contract\Arrayable;
use Countable;
use Iterator;
use OutOfBoundsException;
use Quant\Core\Contract\Comparable;
use Quant\Core\Contract\Equatable;
use TypeError;

/**
 * @template TValue
 * @implements Iterator<int, TValue>
 * @implements  ArrayAccess<int, TValue>
 */
abstract class AbstractList implements Arrayable, ArrayAccess, Iterator, Countable, Equatable
{
    /**
     * @var array<int, TValue>
     */
    protected array $data = [];

    /**
     * \Iterator Interface
     * @var int
     */
    protected int $position = 0;


    final public function __construct()
    {
    }


    /**
     * @param mixed ...$items
     *
     * @return static
     */
    public static function make(...$items): static
    {
        $self = new static();

        foreach ($items as $item) {
            $self[] = $item;
        }

        return $self;
    }


    abstract public function getType(): string;


    /**
     * @param callable $mapFn
     *
     * @return static
     */
    public function map(callable $mapFn): static
    {
        array_map($mapFn, $this->data);

        return $this;
    }


    /**
     * @param callable $findFn
     *
     * @return null|static
     */
    public function findBy(callable $findFn): null|static
    {
        $matches = [];
        foreach ($this->data as $resource) {
            if ($findFn($resource) === true) {
                $matches[] = $resource;
            }
        }

        return count($matches) === 0 ? null : static::make(...$matches);
    }


    /**
     * @return ?TValue
     */
    public function peek(): mixed
    {
        $count = count($this->data);
        return !$count ? null : $this->data[$count - 1];
    }


    public function equals(Equatable $target): bool
    {
        $thisClass = get_class($this);

        if (!($target instanceof $thisClass)) {
            return false;
        }

        /**
         * @var AbstractList<TValue> $td
         */
        $td = $target->toArray();
        if (count($td) !== count($this)) {
            return false;
        }

        $type = $this->getType();
        $isEquatable  = is_a($type, Equatable::class, true);
        $isComparable = is_a($type, Comparable::class, true);

        foreach ($td as $i => $entity) {
            if ($isEquatable) {
                if ($entity->equals($this[$i]) === false) {
                    return false;
                }
            } elseif ($isComparable) {
                if ($entity->compareTo($this[$i]) !== 0) {
                    return false;
                }
            } else {
                if (!$this->compareItems($this[$i], $entity)) {
                    return false;
                }
            }
        }

        return true;
    }


    protected function compareItems(mixed $lft, mixed $rgt): bool
    {
        return $lft === $rgt;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     *
     * @throws OutOfBoundsException
     */
    private function doInsert(mixed $offset, mixed $value)
    {
        if (!is_null($offset) && !is_int($offset)) {
            throw new OutOfBoundsException(
                "expected integer key for \"offset\", " .
                "but got type: " . (gettype($offset))
            );
        }

        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @throws TypeError
     */
    private function assertTypeFor(mixed $value): bool
    {
        $entityType = $this->getType();

        // instanceof has higher precedence, so
        // (!$value instanceof $entityType)
        // would also be a valid expression
        if (!($value instanceof $entityType)) {
            /** @var object $value */
            throw new TypeError(
                "Expected type \"$entityType\" for value-argument, got " . gettype($value)
            );
        }

        return true;
    }


// -------------------------
//  ArrayAccess Interface
// -------------------------

    /**
     * @throws TypeError|OutOfBoundsException if $value is not of the type defined
     * with this getType, or f $offset is not an int
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->assertTypeFor($value);
        $this->doInsert($offset, $value);
    }


    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }


    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }


// --------------------------
//  Iterator Interface
// --------------------------

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function current(): mixed
    {
        return $this->data[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    /**
     * @inheritdoc
     */
    public function valid(): bool
    {
        return isset($this->data[$this->position]);
    }

// --------------------------
//  Iterator Interface
// --------------------------

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }


// --------------------------
//  Arrayable interface
// --------------------------

    /**
     * @return array<mixed, TValue>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
