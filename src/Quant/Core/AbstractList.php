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
 * List supporting Generic types.
 * Each class deriving from AbstractList must provide information about the type maintained
 * with instances of this list via `getType`.
 * In addition to the interfaces implemented by this class, additional methods are provided
 * that help with filtering or looking up entries: #findBy, #peek#, #map
 *
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


    /**
     * Constructor.
     * Final to allow new static();
     *
     * @see make
     */
    final public function __construct()
    {
    }


    /**
     * Factory method for easily creating instances of the implementing class.
     *
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


    /**
     * Returns the class name of the entity-type this list should maintain
     * entries of.
     *
     * @return string
     */
    abstract public function getType(): string;


    /**
     * Applies the map function to this data and returns **this** list.
     *
     * @param callable $mapFn The callable to pass to the callback submitted to
     * array_map()
     *
     * @return static
     */
    public function map(callable $mapFn): static
    {
        array_map($mapFn, $this->data);

        return $this;
    }


    /**
     * Returns a new AbstractList containing all the entries for which the callable returned `true`.
     * Returns null if no matches were found.
     *
     * @param callable $findFn A callback. Return true in the function to indicate a match. First match will
     * be returned. The callback is passed the current entry.
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
     * Returns the element at the head of the AbstractList, or null if the list is empty.
     *
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


    /**
     * Method called by the abstract list if containing items are neither Equatable nor Comparable.
     * Override to implement comparator.
     *
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    protected function compareItems(mixed $a, mixed $b): bool
    {
        return $a === $b;
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
     * @param mixed  $value
     * @return bool
     *
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
