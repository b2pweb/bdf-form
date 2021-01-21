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
        $this->value = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ElementInterface $element)
    {
        if (is_string($this->value)) {
            /** @var T */
            return new $this->value;
        }

        if (is_callable($this->value)) {
            return ($this->value)($element);
        }

        if (is_object($this->value)) {
            return clone $this->value;
        }

        return $this->value;
    }
}
