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
 * Generate a value by call its constructor without arguments
 * Properties will be filled directly by hydrators
 *
 * @template T as object
 * @implements ValueGeneratorInterface<T>
 */
final class DefaultConstructorValueGenerator implements ValueGeneratorInterface
{
    /**
     * @var T|null
     */
    private ?object $attachment = null;

    public function __construct(
        /**
         * @var class-string<T>
         */
        private readonly string $class,
    ) {}

    #[Override]
    public function attach(mixed $entity): void
    {
        if (!$entity instanceof $this->class) {
            throw new InvalidArgumentException(sprintf('Cannot attach a value of type %s, expecting %s', get_debug_type($entity), $this->class));
        }

        $this->attachment = $entity;
    }

    #[Override]
    public function generate(ElementInterface $element): object
    {
        if ($this->attachment !== null) {
            return $this->attachment;
        }

        return new $this->class;
    }

    #[Override]
    public function finalize(object|array $value): object
    {
        assert($value instanceof $this->class);

        return $value;
    }
}
