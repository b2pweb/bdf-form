<?php

namespace Bdf\Form\Leaf;

use BadMethodCallException;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\View\ElementViewInterface;
use Bdf\Validator\ValidatorBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Wrap a leaf element for create a root element
 * Useful for create a singleton form
 */
final class LeafRootElement implements RootElementInterface
{
    /**
     * @var ElementInterface
     */
    private $element;


    /**
     * LeafRootElement constructor.
     *
     * @param ElementInterface $element
     */
    public function __construct(ElementInterface $element)
    {
        $this->element = $element;
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): ElementInterface
    {
        $this->element->submit($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($entity): ElementInterface
    {
        $this->element->import($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value()
    {
        return $this->element->value();
    }

    /**
     * {@inheritdoc}
     */
    public function httpValue()
    {
        return $this->element->httpValue();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->element->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function error(): FormError
    {
        return $this->element->error();
    }

    /**
     * {@inheritdoc}
     */
    public function container(): ?ChildInterface
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ChildInterface $container): ElementInterface
    {
        throw new BadMethodCallException('Cannot set a container on a root element');
    }

    /**
     * {@inheritdoc}
     */
    public function root(): RootElementInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        return $this->element->view($field);
    }

    /**
     * {@inheritdoc}
     */
    public function submitButton(): ?ButtonInterface
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator(): ValidatorInterface
    {
        return (new ValidatorBuilder())->getValidator();
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAccessor(): PropertyAccessorInterface
    {
        return new PropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function constraintGroups(): array
    {
        return [Constraint::DEFAULT_GROUP];
    }
}
