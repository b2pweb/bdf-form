<?php

namespace Bdf\Form\Aggregate\Collection;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Override;

/**
 * The dependency tree level
 *
 * @internal
 * @implements IteratorAggregate<string, string[]>
 */
final class Level implements IteratorAggregate
{
    private int $number;
    private ?Level $prev;
    private ?Level $next = null;
    private ?Level $last = null;

    /**
     * Array of elements dependencies
     *
     * @var string[][]
     */
    private array $elements = [];


    /**
     * Level constructor.
     *
     * @param Level|null $prev
     * @param int $number
     */
    public function __construct(?Level $prev = null, int $number = 0)
    {
        $this->prev   = $prev;
        $this->number = $number;
    }

    /**
     * Add dependencies to the level
     *
     * @param string $name The element name
     * @param array $dependencies The element dependencies
     *
     * @return int[] Associative array, with element name as key, and element level as value
     */
    public function add(string $name, array $dependencies): array
    {
        $result = [
            $name => $this->number
        ];

        $this->elements[$name] = $dependencies;

        foreach ($dependencies as $dependency) {
            $result = array_merge($result, $this->shift($dependency));
        }

        return $result;
    }

    /**
     * Check if the level contains the element
     *
     * @param string $element The element name
     *
     * @return bool
     */
    public function has(string $element): bool
    {
        return isset($this->elements[$element]);
    }

    /**
     * Move an element to the next level (the element becomes a dependency)
     *
     * @param string $element The element name
     *
     * @return int[] The result of add()
     */
    public function shift(string $element): array
    {
        if ($this->next === null) {
            $this->next = new self($this, $this->number + 1);
            $this->last = $this->next;
        }

        if ($this->has($element)) {
            $dependencies = $this->elements[$element];
            unset($this->elements[$element]);
        } else {
            $dependencies = [];
        }

        $result = $this->next->add($element, $dependencies);

        if ($this->next->last !== null) {
            $this->last = $this->next->last;
        }

        return $result;
    }

    /**
     * @return int
     */
    public function number(): int
    {
        return $this->number;
    }

    /**
     * Get the previous level (lvl n-1)
     *
     * @return Level|null
     */
    public function prev(): ?Level
    {
        return $this->prev;
    }

    /**
     * Get the last level (queue of the list)
     * Can return NULL if the current level is the last element
     *
     * @return Level|null
     */
    public function last(): ?Level
    {
        return $this->last;
    }

    /**
     * Get the next level (lvl n+1)
     * Can return NULL if the current level is the last element
     *
     * @return Level|null
     */
    public function next(): ?Level
    {
        return $this->next;
    }

    /**
     * {@inheritdoc}
     *
     * @return Iterator<string, string[]>
     */
    #[Override]
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * Reset the dependencies of the element
     *
     * @param string $name The element name
     */
    public function reset(string $name): void
    {
        if ($this->has($name)) {
            $this->elements[$name] = [];
        }
    }

    /**
     * Remove an element from the index
     *
     * @param string $name
     */
    public function remove(string $name): void
    {
        unset($this->elements[$name]);
    }
}
