<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use Closure;
use Override;

/**
 * Generate a value using a closure
 *
 * @template T as array|object
 * @implements ValueGeneratorInterface<T>
 */
final class ClosureValueGenerator implements ValueGeneratorInterface
{
    /**
     * @var T|null
     */
    private array|object|null $attachment = null;

    public function __construct(
        /**
         * @var Closure(ElementInterface):T
         */
        private readonly Closure $generator,
    ) {}

    #[Override]
    public function attach(mixed $entity): void
    {
        $this->attachment = $entity;
    }

    #[Override]
    public function generate(ElementInterface $element): object|array
    {
        if ($this->attachment !== null) {
            return $this->attachment;
        }

        return ($this->generator)($element);
    }

    #[Override]
    public function finalize(object|array $value): object|array
    {
        /** @var T */
        return $value;
    }
}
