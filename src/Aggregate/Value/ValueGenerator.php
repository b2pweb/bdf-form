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
 */
final class ValueGenerator implements ValueGeneratorInterface
{
    /**
     * @var object|callable|array|class-string
     */
    private $value;


    /**
     * ValueGenerator constructor.
     *
     * @param callable|object|class-string|array $value
     */
    public function __construct($value = [])
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function attach($entity): void
    {
        $this->value = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ElementInterface $element)
    {
        if (is_string($this->value)) {
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
