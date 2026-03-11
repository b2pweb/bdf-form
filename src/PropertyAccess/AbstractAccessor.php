<?php

namespace Bdf\Form\PropertyAccess;

use Bdf\Form\Child\ChildInterface;
use Override;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function is_string;

/**
 * Base access implementation
 */
abstract class AbstractAccessor implements AccessorInterface
{
    private ?string $propertyName = null;

    /**
     * @var callable|null
     */
    protected mixed $transformer = null;

    /**
     * @var callable|null
     */
    protected mixed $customAccessor = null;
    protected ?PropertyAccessorInterface $propertyAccessor = null;
    protected ?ChildInterface $input = null;

    /**
     * @param string|callable|null $propertyName
     * @param callable|null $transformer
     * @param callable|null $customAccessor
     */
    public function __construct(string|callable|null $propertyName = null, ?callable $transformer = null, ?callable $customAccessor = null)
    {
        if ($propertyName !== null && !is_string($propertyName)) {
            $customAccessor = $transformer;
            $transformer = $propertyName;
            $propertyName = null;
        }

        $this->propertyName = $propertyName;
        $this->transformer = $transformer;
        $this->customAccessor = $customAccessor;
    }

    #[Override]
    final public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): void
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    #[Override]
    final public function setFormElement(?ChildInterface $formElement): void
    {
        $this->input = $formElement;
    }

    /**
     * Get the property path of entity property
     *
     * @param array|object $target
     * @return string
     */
    final protected function prepareAccessorPath(array|object $target): string
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
    final protected function getPropertyName(): string
    {
        if ($this->propertyName === null && $this->input) {
            $this->propertyName = $this->input->name();
        }

        return (string) $this->propertyName;
    }
}
