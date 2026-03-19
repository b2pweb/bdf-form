<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use ReflectionProperty;

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
     * @var array<non-empty-string, ReflectionProperty>
     */
    private array $elementProperties = [];

    /**
     * @var array<string, list<ChildBuilderAttributeInterface>>
     */
    private array $childAttributes = [];

    /**
     * @param non-empty-string $name
     * @param ReflectionProperty $property
     * @return void
     */
    public function addButtonProperty(string $name, ReflectionProperty $property): void
    {
        $this->buttonProperties[$name] = $property;
    }

    /**
     * @param non-empty-string $name
     * @param ReflectionProperty $property
     * @return void
     */
    public function addElementProperty(string $name, ReflectionProperty $property): void
    {
        $this->elementProperties[$name] = $property;
    }

    public function addChildAttribute(string $elementName, ChildBuilderAttributeInterface $attribute): void
    {
        $this->childAttributes[$elementName][] = $attribute;
    }

    /**
     * @return array<non-empty-string, ReflectionProperty>
     */
    public function buttonProperties(): array
    {
        return $this->buttonProperties;
    }

    /**
     * @return array<non-empty-string, ReflectionProperty>
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

    /**
     * Get child attributes manually registered for the given element name
     * Those attributes are generally registered by the {@see MethodChildBuilderAttributeInterface} attributes on methods
     *
     * @param string $name
     * @return list<ChildBuilderAttributeInterface>
     */
    public function registeredChildAttributes(string $name): array
    {
        return $this->childAttributes[$name] ?? [];
    }
}
