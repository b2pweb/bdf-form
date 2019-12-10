<?php

namespace Bdf\Form\Aggregate\Collection;

use ArrayIterator;
use IteratorAggregate;

/**
 * The dependency tree level
 *
 * @internal
 */
final class Level implements IteratorAggregate
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var Level
     */
    private $prev;

    /**
     * @var Level
     */
    private $next;

    /**
     * @var Level
     */
    private $last;

    /**
     * Array of elements dependencies
     *
     * @var string[][]
     */
    private $elements = [];


    /**
     * Level constructor.
     *
     * @param Level $prev
     * @param int $number
     */
    public function __construct(Level $prev = null, $number = 0)
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
    public function add($name, array $dependencies)
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
    public function has($element)
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
    public function shift($element)
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
    public function number()
    {
        return $this->number;
    }

    /**
     * Get the previous level (lvl n-1)
     *
     * @return Level
     */
    public function prev()
    {
        return $this->prev;
    }

    /**
     * Get the last level (queue of the list)
     * Can return NULL if the current level is the last element
     *
     * @return Level|null
     */
    public function last()
    {
        return $this->last;
    }

    /**
     * Get the next level (lvl n+1)
     * Can return NULL if the current level is the last element
     *
     * @return Level
     */
    public function next()
    {
        return $this->next;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * Reset the dependencies of the element
     *
     * @param string $name The element name
     */
    public function reset($name)
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
    public function remove($name)
    {
        unset($this->elements[$name]);
    }
}
