<?php

namespace Bdf\Form\PropertyAccess;

use Bdf\Form\Child\ChildInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Base access implementation
 */
abstract class AbstractAccessor implements AccessorInterface
{
    /**
     * @var string|null
     */
    private $propertyName;

    /**
     * @var callable|null
     */
    protected $transformer;

    /**
     * @var callable|null
     */
    protected $customAccessor;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var ChildInterface
     */
    protected $input;


    /**
     * Getter constructor.
     *
     * @param string|callable $propertyName
     * @param callable|null $transformer
     * @param callable|null $customAccessor
     */
    public function __construct($propertyName = null, ?callable $transformer = null, ?callable $customAccessor = null)
    {
        if (is_callable($propertyName)) {
            $customAccessor = $transformer;
            $transformer = $propertyName;
            $propertyName = null;
        }

        $this->propertyName = $propertyName;
        $this->transformer = $transformer;
        $this->customAccessor = $customAccessor;
    }

    /**
     * {@inheritdoc}
     */
    final public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): void
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    final public function setFormElement(ChildInterface $formElement): void
    {
        $this->input = $formElement;
    }

    /**
     * Get the property path of entity property
     *
     * @param array|object $target
     * @return string
     */
    final protected function prepareAccessorPath($target): string
    {
        $propertyName = $this->getPropertyName();

        if (is_array($target)) {
            $path = '';

            foreach (explode('.', $propertyName) as $part) {
                $path .= '['.$part.']';
            }

            return $path;
        }

        return $propertyName;
    }

    /**
     * Get the property name
     *
     * @return string
     */
    final protected function getPropertyName()
    {
        if ($this->propertyName === null) {
            $this->propertyName = $this->input->name();
        }

        return $this->propertyName;
    }
}
