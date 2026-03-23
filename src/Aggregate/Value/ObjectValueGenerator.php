<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use InvalidArgumentException;
use Override;

use function assert;
use function get_class;
use function get_debug_type;
use function sprintf;

/**
 * Generate a value using an object as template which will be cloned for each generation
 *
 * @template T as object
 * @implements ValueGeneratorInterface<T>
 */
final class ObjectValueGenerator implements ValueGeneratorInterface
{
    /**
     * @var T|null
     */
    private ?object $attachment = null;

    public function __construct(
        /**
         * @var T
         */
        private readonly object $value,
    ) {}

    #[Override]
    public function attach(mixed $entity): void
    {
        if (!$entity instanceof $this->value) {
            throw new InvalidArgumentException(sprintf('Cannot attach a value of type %s, expecting %s', get_debug_type($entity), get_class($this->value)));
        }

        $this->attachment = $entity;
    }

    #[Override]
    public function generate(ElementInterface $element): object
    {
        if ($this->attachment !== null) {
            return $this->attachment;
        }

        return clone $this->value;
    }

    #[Override]
    public function finalize(object|array $value): object
    {
        assert($value instanceof $this->value);

        /** @var T */
        return $value;
    }
}
