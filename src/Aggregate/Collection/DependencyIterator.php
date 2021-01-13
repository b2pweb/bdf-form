<?php

namespace Bdf\Form\Aggregate\Collection;

use Bdf\Form\Child\ChildInterface;
use Iterator;

/**
 * Iterate over @see DependencyTree
 *
 * Iterate first on the last level, and go to lower levels, to root
 */
final class DependencyIterator implements Iterator
{
    /**
     * @var ChildInterface[]
     */
    private $children;

    /**
     * @var Level
     */
    private $first;

    /**
     * @var bool
     */
    private $reverse;

    /**
     * @var Level|null
     */
    private $currentLevel;

    /**
     * @var Iterator|null
     */
    private $levelIterator;


    /**
     * DependencyIterator constructor.
     *
     * @param ChildInterface[] $children
     * @param Level $first
     * @param bool $reverse Does iterate on reverse order on levels ?
     */
    public function __construct(array $children, Level $first, $reverse = true)
    {
        $this->children = $children;
        $this->first    = $first;
        $this->reverse  = $reverse;
    }

    /**
     * {@inheritdoc}
     *
     * @return ChildInterface
     */
    public function current()
    {
        return $this->children[$this->key()];
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress PossiblyNullReference
     * @psalm-suppress PossiblyNullReference
     */
    public function next()
    {
        $this->levelIterator->next();

        // The level iterator can be invalid if the level is empty
        // We need to skip empty levels
        while (!$this->levelIterator->valid()) {
            $this->levelIterator = null;
            $this->currentLevel  = $this->reverse ? $this->currentLevel->prev() : $this->currentLevel->next();

            // There is no more level, the iterator will be "invalid"
            if ($this->currentLevel === null) {
                return;
            }

            // Create the new iterator, and reset
            $this->levelIterator = $this->currentLevel->getIterator();
            $this->levelIterator->rewind();
        }

        // The children is not already registered
        // We should skip this step
        if (!isset($this->children[$this->key()])) {
            $this->next();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress PossiblyNullReference
     */
    public function key()
    {
        return $this->levelIterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->levelIterator !== null && $this->levelIterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->currentLevel  = $this->first;
        $this->levelIterator = $this->currentLevel->getIterator();
        $this->levelIterator->rewind();

        // The child is not registered, we should go next
        // Or the iterator is not valid
        if (
            !$this->levelIterator->valid()
            || !isset($this->children[$this->key()])
        ) {
            $this->next();
        }
    }
}
