<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\Element\ElementAttributeChildBuilderAdapter;
use Bdf\Form\ElementInterface;
use ReflectionProperty;

/**
 * Stores metadata of a form element
 */
final class ElementPropertyMetadata
{
    public function __construct(
        public readonly string $name,
        public readonly ReflectionProperty $property,

        /**
         * The type of the form field element
         * By default, this is same as the property type
         *
         * @var class-string<ElementInterface>
         */
        public readonly string $elementType,

        /**
         * Attributes attached to this property
         *
         * @var list<ChildBuilderAttributeInterface>
         */
        public private(set) array $attributes,
    ) {}

    public function addAttribute(ChildBuilderAttributeInterface $attribute): void
    {
        $this->attributes[] = $attribute;
    }

    /**
     * Check if the property metadata has the given attribute
     *
     * @param class-string $class
     */
    public function hasAttribute(string $class): bool
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute instanceof $class) {
                return true;
            }

            if ($attribute instanceof ElementAttributeChildBuilderAdapter && $attribute->is($class)) {
                return true;
            }
        }

        return false;
    }
}
