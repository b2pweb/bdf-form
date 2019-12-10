<?php

namespace Bdf\Form\Aggregate\Collection;

use Bdf\Form\Aggregate\ChildAggregateInterface;
use Bdf\Form\Child\ChildInterface;
use Iterator;

/**
 * Handle form children dependencies
 *
 * All element are align on a "level"
 * - The "root" level contains all children without dependencies, or the newest elements
 * - All child will be added to the "root" level
 * - When a child become a dependency of a new added element, it will be shift to higher level (recursively on dependencies of the dependency)
 * - A dependency can be registered BEFORE be added on the tree (its depth will be computed before)
 *
 * Example :
 *
 * add(E1())       -> lvl0(E1)                          [E1 added to lvl0]
 * add(E2())       -> lvl0(E1, E2)                      [E2 added to lvl0]
 * add(E3(E2))     -> lvl0(E1, E3), lvl1(E2)            [E3 added to lvl0, E2 shift to lvl1]
 * add(E4(E2, E3)) -> lvl0(E1, E4), lvl1(E3), lvl2(E2)  [E4 added to lvl0, E3 shift to lvl1, E2 shift to lvl2]
 *
 * The iterator of the dependency tree will iterate over "higher" dependencies before, and "root" children at the end
 */
final class DependencyTree implements \ArrayAccess, \IteratorAggregate, \Countable, ChildrenCollectionInterface
{
    /**
     * @var ChildInterface[]
     */
    private $children = [];

    /**
     * The first level of dependencies
     *
     * @var Level
     */
    private $root;

    /**
     * The last level of dependencies
     *
     * @var Level
     */
    private $last;

    /**
     * Get the level of each elements
     * An element can be register on the dependency tree, but not in the children array
     *
     * @var int[]
     */
    private $depth = [];


    /**
     * DependencyTree constructor.
     */
    public function __construct()
    {
        $this->root = new Level();
        $this->last = $this->root;
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

        $level = $this->level($name);

        if ($level->number() === 0) {
            // The child is not part of a dependency
            // Web can remove from index safely
            $level->remove($name);
            unset($this->depth[$name]);
        } else {
            // The child is part of a dependency
            // Keep it in the index, but remove its dependencies
            $level->reset($name);
        }

        unset($this->children[$name]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->children[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->add($value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseIterator(): Iterator
    {
        return new DependencyIterator($this->children, $this->last, true);
    }

    /**
     * {@inheritdoc}
     */
    public function forwardIterator(): Iterator
    {
        return new DependencyIterator($this->children, $this->root, false);
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

        $collection = clone $this;

        $collection->children = $children;

        return $collection;
    }

    /**
     * Get the level which the child should be added
     *
     * @param ChildInterface|string $child
     *
     * @return Level
     */
    protected function level($child)
    {
        if (!is_string($child)) {
            $child = $child->name();
        }

        if (!isset($this->depth[$child])) {
            return $this->root;
        }

        $target = $this->depth[$child];
        $level  = $this->root;

        while ($target > $level->number()) {
            $level = $level->next();
        }

        return $level;
    }

    /**
     * Add a child to the dependency tree
     *
     * @param string $name
     * @param ChildInterface $child
     */
    protected function addNamed($name, ChildInterface $child)
    {
        $this->children[$name] = $child;

        $level = $this->level($child);

        $this->depth = array_merge($this->depth, $level->add(
            $name,
            $this->extractDependencies($child, $level)
        ));

        $this->last = $level->last() ?: $level;
    }

    /**
     * Extract dependencies from a child element
     * Remove dependencies that has been already registered and with higher level than child
     *
     * @param ChildInterface $child
     * @param Level $level
     *
     * @return string[]
     */
    protected function extractDependencies(ChildInterface $child, Level $level)
    {
        $dependencies = [];

        foreach ($child->dependencies() as $dependency) {
            if (!isset($this->depth[$dependency])) {
                $dependencies[] = $dependency;
                continue;
            }

            if ($this->depth[$dependency] > $level->number()) {
                continue;
            }

            $dependencies[] = $dependency;
        }

        return $dependencies;
    }
}
