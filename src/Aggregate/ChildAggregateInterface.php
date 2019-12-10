<?php

namespace Bdf\Form\Aggregate;

use ArrayAccess;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;
use IteratorAggregate;

/**
 * Form element consists of an aggregation of sub-elements wrapped into a ChildInterface
 *
 * The children can be acceded using array access, with child's name as offset, or using the iterator
 */
interface ChildAggregateInterface extends ElementInterface, ArrayAccess, IteratorAggregate
{
    /**
     * {@inheritdoc}
     *
     * Get a child element by its name
     *
     * @param string $offset The child name
     */
    public function offsetGet($offset): ChildInterface;

    /**
     * {@inheritdoc}
     *
     * Check if the child exists
     *
     * @param string $offset The child name
     */
    public function offsetExists($offset): bool;

    /**
     * Operation not allowed
     *
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value);

    /**
     * Operation not allowed
     *
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset);

    /**
     * {@inheritdoc}
     *
     * Iterates over children
     *
     * @return iterable|ChildInterface[]
     */
    public function getIterator();
}
