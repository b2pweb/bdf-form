<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Form\FormBuilderAttributeInterface;
use ReflectionProperty;

use function array_push;

/**
 * Store metadata about the form that is currently processed
 *
 * @internal
 */
final class ProcessorMetadata
{
    /**
     * @var array<non-empty-string, ReflectionProperty>
     */
    private array $buttonProperties = [];

    /**
     * @var array<non-empty-string, ElementPropertyMetadata>
     */
    private array $elementProperties = [];

    /**
     * Lis of attributes declared on the form class / DTO class
     *
     * @var list<FormBuilderAttributeInterface>
     */
    public private(set) array $formAttributes = [];

    /**
     * Check if the form has the given attribute on it
     *
     * @param class-string $name
     * @return bool
     */
    public function hasFormAttribute(string $name): bool
    {
        foreach ($this->formAttributes as $formAttribute) {
            if ($formAttribute instanceof $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param non-empty-string $name
     * @param ReflectionProperty $property
     * @return void
     */
    public function addButtonProperty(string $name, ReflectionProperty $property): void
    {
        $this->buttonProperties[$name] = $property;
    }

    public function addElementProperty(ElementPropertyMetadata $metadata): void
    {
        $this->elementProperties[$metadata->name] = $metadata;
    }

    public function addChildAttribute(string $elementName, ChildBuilderAttributeInterface $attribute): void
    {
        $this->elementProperties[$elementName]->addAttribute($attribute);
    }

    public function addFormAttribute(FormBuilderAttributeInterface $attribute): void
    {
        $this->formAttributes[] = $attribute;
    }

    /**
     * @return array<non-empty-string, ReflectionProperty>
     */
    public function buttonProperties(): array
    {
        return $this->buttonProperties;
    }

    /**
     * @return array<non-empty-string, ElementPropertyMetadata>
     */
    public function elementProperties(): array
    {
        return $this->elementProperties;
    }

    /**
     * Check if the given property has already been registered
     *
     * @param string $name The property name
     * @return bool
     */
    public function hasProperty(string $name): bool
    {
        return isset($this->buttonProperties[$name]) || isset($this->elementProperties[$name]);
    }
}
