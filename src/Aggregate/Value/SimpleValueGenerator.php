<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use Override;

/**
 * Simply return the stored value
 *
 * @template T as array|object
 * @implements ValueGeneratorInterface<T>
 */
final class SimpleValueGenerator implements ValueGeneratorInterface
{
    public function __construct(
        /**
         * @var T
         */
        private array|object $value = [],
    ) {}

    #[Override]
    public function attach(mixed $entity): void
    {
        $this->value = $entity;
    }

    #[Override]
    public function generate(ElementInterface $element): object|array
    {
        return $this->value;
    }

    #[Override]
    public function finalize(object|array $value): object|array
    {
        /** @var T */
        return $value;
    }
}
