<?php

namespace Bdf\Form\Leaf;

use BadMethodCallException;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Util\RootFlagsTrait;
use Bdf\Form\View\ElementViewInterface;
use OutOfBoundsException;
use Override;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * Wrap a leaf element for create a root element
 * Useful for create a singleton form
 */
final class LeafRootElement implements RootElementInterface
{
    use RootFlagsTrait;

    public function __construct(
        private readonly ElementInterface $element,
    ) {}

    #[Override]
    public function submit(mixed $data): static
    {
        $this->element->submit($data);

        return $this;
    }

    #[Override]
    public function patch(mixed $data): static
    {
        $this->element->patch($data);

        return $this;
    }

    #[Override]
    public function import(mixed $entity): static
    {
        $this->element->import($entity);

        return $this;
    }

    #[Override]
    public function value(): mixed
    {
        return $this->element->value();
    }

    #[Override]
    public function httpValue(): mixed
    {
        return $this->element->httpValue();
    }

    #[Override]
    public function valid(): bool
    {
        return $this->element->valid();
    }

    #[Override]
    public function failed(): bool
    {
        // Do not use $this->element->failed() because it may be not implemented
        return !$this->valid();
    }

    #[Override]
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $this->element->error($field);
    }

    #[Override]
    public function container(): ?ChildInterface
    {
        return null;
    }

    #[Override]
    public function setContainer(ChildInterface $container): ElementInterface
    {
        throw new BadMethodCallException('Cannot set a container on a root element');
    }

    #[Override]
    public function root(): RootElementInterface
    {
        return $this;
    }

    #[Override]
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        return $this->element->view($field);
    }

    #[Override]
    public function submitButton(): ?ButtonInterface
    {
        return null;
    }

    #[Override]
    public function button(string $name): ButtonInterface
    {
        throw new OutOfBoundsException('A leaf element do not have any buttons');
    }

    #[Override]
    public function getValidator(): ValidatorInterface
    {
        return new ValidatorBuilder()->getValidator();
    }

    #[Override]
    public function getPropertyAccessor(): PropertyAccessorInterface
    {
        return new PropertyAccessor();
    }

    #[Override]
    public function constraintGroups(): array
    {
        return [Constraint::DEFAULT_GROUP];
    }
}
