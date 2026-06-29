<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Override;
use ReflectionProperty;

/**
 * Fill the form properties using reflection
 */
final readonly class PostConfigureReflectionSetProperties implements PostConfigureInterface
{
    public function __construct(
        /**
         * Properties which store form elements
         * The key is the element name, and value is the reflection property
         *
         * @var array<non-empty-string, ElementPropertyMetadata>
         */
        private array $elementProperties,
        /**
         * Properties which store form buttons
         * The key is the button name, and value is the reflection property
         *
         * @var array<non-empty-string, ReflectionProperty>
         */
        private array $buttonProperties,
    ) {}

    #[Override]
    public function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        foreach ($this->elementProperties as $name => $metadata) {
            $metadata->property->setValue($form, $inner[$name]->element());
        }

        foreach ($this->buttonProperties as $name => $reflection) {
            $reflection->setValue($form, $form->root()->button($name));
        }
    }
}
