<?php

namespace Bdf\Form\Aggregate;

use ArrayAccess;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;
use Iterator;
use IteratorAggregate;

/**
 * Form element consists of an aggregation of sub-elements wrapped into a ChildInterface
 *
 * The children can be acceded using array access, with child's name as offset, or using the iterator
 *
 * @template T
 * @extends ElementInterface<T>
 * @extends ArrayAccess<string, ChildInterface>
 * @extends IteratorAggregate<string, ChildInterface>
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
     * {@inheritdoc}
     *
     * Operation not allowed
     *
     * @param string $offset The child name
     * @param ChildInterface $value
     *
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value): void;

    /**
     * {@inheritdoc}
     *
     * Operation not allowed
     *
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset): void;

    /**
     * {@inheritdoc}
     *
     * Iterates over children
     *
     * @return Iterator<string, ChildInterface>
     */
    public function getIterator(): Iterator;
}
