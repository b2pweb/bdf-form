<?php

namespace Bdf\Form\Aggregate\Collection;

use ArrayIterator;
use Bdf\Form\Aggregate\ChildAggregateInterface;
use Bdf\Form\Child\ChildInterface;
use Countable;
use Iterator;
use IteratorAggregate;
use Override;

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
    private array $children = [];

    /**
     * Flag to know if the form has view dependencies in its children
     */
    private bool $hasViewDependencies = false;


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

    #[Override]
    public function add(ChildInterface $child): void
    {
        $this->addNamed($child->name(), $child);
    }

    #[Override]
    public function has(string $name): bool
    {
        return isset($this->children[$name]);
    }

    #[Override]
    public function remove(string $name): bool
    {
        if (!$this->has($name)) {
            return false;
        }

        unset($this->children[$name]);

        return true;
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    #[Override]
    public function offsetGet(mixed $offset): ChildInterface
    {
        return $this->children[$offset];
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->add($value);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    #[Override]
    public function count(): int
    {
        return count($this->children);
    }

    #[Override]
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->children);
    }

    #[Override]
    public function reverseIterator(): Iterator
    {
        return new ArrayIterator($this->hasViewDependencies ? array_reverse($this->children) : $this->children);
    }

    #[Override]
    public function forwardIterator(): Iterator
    {
        return new ArrayIterator($this->children);
    }

    #[Override]
    public function all(): array
    {
        return $this->children;
    }

    #[Override]
    public function duplicate(ChildAggregateInterface $newParent): ChildrenCollectionInterface
    {
        $children = [];

        foreach ($this->children as $key => $child) {
            $children[$key] = $child->setParent($newParent);
        }

        $collection = new self();

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
    private function addNamed(string $name, ChildInterface $child): void
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
