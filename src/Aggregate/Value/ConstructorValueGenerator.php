<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use InvalidArgumentException;
use Override;

use function assert;
use function is_array;
use function sprintf;

/**
 * Instantiate the form value using its constructor with named parameters
 * Hydrators will fill an array which will be used as parameters of the class constructor
 *
 * @template T as object
 * @implements ValueGeneratorInterface<T>
 */
final class ConstructorValueGenerator implements ValueGeneratorInterface
{
    /**
     * @var T|array|null
     */
    private array|object|null $attachment = null;

    public function __construct(
        /**
         * @var class-string<T>
         */
        private readonly string $class,
    ) {}

    #[Override]
    public function attach(mixed $entity): void
    {
        if (is_array($entity) || $entity instanceof $this->class) {
            $this->attachment = $entity;
            return;
        }

        throw new InvalidArgumentException(sprintf('Expected array or instance of %s, %s given on %s::attach()', $this->class, get_debug_type($entity), self::class));
    }

    #[Override]
    public function generate(ElementInterface $element): object|array
    {
        return $this->attachment ?? [];
    }

    #[Override]
    public function finalize(object|array $value): object
    {
        if (is_array($value)) {
            $value = new ($this->class)(...$value);
        }

        assert($value instanceof $this->class);

        return $value;
    }
}
