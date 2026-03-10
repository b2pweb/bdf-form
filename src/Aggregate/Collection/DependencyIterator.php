<?php

namespace Bdf\Form\Aggregate\Collection;

use Bdf\Form\Child\ChildInterface;
use Iterator;
use Override;

use function assert;

/**
 * Iterate over @see DependencyTree
 *
 * Iterate first on the last level, and go to lower levels, to root
 *
 * @implements Iterator<ChildInterface>
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

    #[Override]
    public function current(): ChildInterface
    {
        return $this->children[$this->key()];
    }

    #[Override]
    public function next(): void
    {
        assert($this->levelIterator !== null);
        $this->levelIterator->next();

        // The level iterator can be invalid if the level is empty
        // We need to skip empty levels
        while (!$this->levelIterator->valid()) {
            $this->levelIterator = null;
            $this->currentLevel = $this->reverse ? $this->currentLevel?->prev() : $this->currentLevel?->next();

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

    #[Override]
    public function key(): string|int
    {
        assert($this->levelIterator !== null);
        return $this->levelIterator->key();
    }

    #[Override]
    public function valid(): bool
    {
        return $this->levelIterator !== null && $this->levelIterator->valid();
    }

    #[Override]
    public function rewind(): void
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
