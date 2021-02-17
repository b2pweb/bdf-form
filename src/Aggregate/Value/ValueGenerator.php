<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;

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
    private $value;

    /**
     * @var callable():T|T|class-string<T>|null
     */
    private $attachment;

    /**
     * ValueGenerator constructor.
     *
     * @param callable():T|T|class-string<T> $value
     */
    public function __construct($value = [])
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function attach($entity): void
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->attachment = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ElementInterface $element)
    {
        $value = $this->attachment ?? $this->value;

        if (is_string($value)) {
            /** @var T */
            return new $value;
        }

        if (is_callable($value)) {
            return ($value)($element);
        }

        // Only clone value if it's not attached
        if (!$this->attachment && is_object($value)) {
            return clone $value;
        }

        return $value;
    }
}
