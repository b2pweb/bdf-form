<?php

namespace Bdf\Form\Aggregate\Collection;

use ArrayAccess;
use Bdf\Form\Aggregate\ChildAggregateInterface;
use Bdf\Form\Child\ChildInterface;
use Countable;
use Iterator;

/**
 * Collection of form child elements
 */
interface ChildrenCollectionInterface extends ArrayAccess, Countable
{
    /**
     * Add a child to the dependency tree
     *
     * @param ChildInterface $child
     */
    public function add(ChildInterface $child): void;

    /**
     * Check if the form child exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Remove the child from the list
     * Do not remove from the dependency index
     *
     * @param string $name
     *
     * @return bool True if the child exists
     */
    public function remove(string $name): bool;

    /**
     * Get the reverse iterator (i.e. iterate on higher dependencies in first)
     *
     * @return Iterator
     */
    public function reverseIterator(): Iterator;

    /**
     * @return Iterator
     */
    public function forwardIterator(): Iterator;

    /**
     * Get all children elements
     *
     * @return ChildInterface[]
     */
    public function all(): array;

    /**
     * Duplicate the children into a new container
     *
     * @param ChildAggregateInterface $newParent The new container
     *
     * @return static The new collection instance
     *
     * @todo rename
     */
    public function duplicate(ChildAggregateInterface $newParent): ChildrenCollectionInterface;
}
