<?php

namespace Bdf\Form\Aggregate\Collection;

use ArrayIterator;
use Bdf\Form\Aggregate\ChildAggregateInterface;
use Bdf\Form\Child\ChildInterface;
use Countable;
use Iterator;
use IteratorAggregate;

/**
 * Simple implementation of children collection for handle dependencies order
 * When a child is added, all its dependencies are moved to the end of the collection
 */
final class ChildrenCollection implements Countable, ChildrenCollectionInterface
{
    /**
     * The collection of children
     *
     * @var ChildInterface[]
     */
    private $children = [];

    /**
     * Flag to know if the form has view dependencies in its children
     *
     * @var boolean
     */
    private $hasViewDependencies = false;


    /**
     * ChildrenCollection constructor.
     *
     * @param ChildInterface[] $children
     */
    public function __construct(array $children = [])
    {
        foreach ($children as $child) {
            $this->add($child);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(ChildInterface $child): void
    {
        $this->addNamed($child->name(), $child);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return isset($this->children[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): bool
    {
        if (!$this->has($name)) {
            return false;
        }

        unset($this->children[$name]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): ChildInterface
    {
        return $this->children[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->add($value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseIterator(): Iterator
    {
        return new ArrayIterator($this->hasViewDependencies ? array_reverse($this->children) : $this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function forwardIterator(): Iterator
    {
        return new ArrayIterator($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(ChildAggregateInterface $newParent): ChildrenCollectionInterface
    {
        $children = [];

        foreach ($this->children as $key => $child) {
            $children[$key] = $child->setParent($newParent);
        }

        $collection = new static();

        $collection->children = $children;
        $collection->hasViewDependencies = $this->hasViewDependencies;

        return $collection;
    }

    /**
     * Add a child to the dependency tree
     *
     * @param string $name
     * @param ChildInterface $child
     */
    private function addNamed($name, ChildInterface $child): void
    {
        $this->children[$name] = $child;
        $this->orderDependencies($child);
    }

    /**
     * Order the collection of children by dependencies
     *
     * @param ChildInterface $child
     */
    private function orderDependencies(ChildInterface $child): void
    {
        if (!$child->dependencies()) {
            return;
        }

        $this->hasViewDependencies = true;

        foreach ($child->dependencies() as $dependency) {
            if (!$this->has($dependency)) {
                continue;
            }

            $dependantChild = $this->children[$dependency];
            unset($this->children[$dependency]);

            $this->add($dependantChild);
        }
    }
}
