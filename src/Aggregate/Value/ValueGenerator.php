<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use Override;

use function class_exists;
use function is_callable;
use function is_object;
use function is_string;

/**
 * The base value generator implementation
 *
 * <code>
 * (new ValueGenerator())->generate($form); // Will generate an empty array
 * (new ValueGenerator(MyEntity::class))->generate($form); // Will call the default constructor of MyEntity
 * (new ValueGenerator($entity))->generate($form); // Will clone the instance of $entity
 * (new ValueGenerator(function (FormInterface $form) { return new MyEntity(...); }))->generate($form); // Custom generator
 * </code>
 *
 * @template T
 * @implements ValueGeneratorInterface<T>
 */
final class ValueGenerator implements ValueGeneratorInterface
{
    /**
     * @var callable():T|T|class-string<T>
     */
    private mixed $value;

    /**
     * @var callable():T|T|class-string<T>|null
     */
    private mixed $attachment = null;

    /**
     * ValueGenerator constructor.
     *
     * @param callable():T|T|class-string<T> $value
     */
    public function __construct(mixed $value = [])
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->value = $value;
    }

    #[Override]
    public function attach(mixed $entity): void
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->attachment = $entity;
    }

    #[Override]
    public function generate(ElementInterface $element): mixed
    {
        $value = $this->attachment ?? $this->value;

        if (is_string($value) && class_exists($value)) {
            /** @var T */
            return new $value;
        }

        if (is_callable($value)) {
            return ($value)($element);
        }

        // Only clone value if it's not attached
        if ($this->attachment === null && is_object($value)) {
            return clone $value;
        }

        /** @var T */
        return $value;
    }
}
