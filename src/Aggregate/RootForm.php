<?php

namespace Bdf\Form\Aggregate;

use BadMethodCallException;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\View\ElementViewInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * Adapt a form element as root element
 *
 * @todo delegation trait
 */
final class RootForm implements RootElementInterface, ChildAggregateInterface
{
    /**
     * @var Form
     */
    private $form;

    /**
     * @var ButtonInterface[]
     */
    private $buttons;

    /**
     * @var ButtonInterface|null
     */
    private $submitButton;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var ValidatorInterface
     */
    private $validator;


    /**
     * RootForm constructor.
     *
     * @param Form $form
     * @param ButtonInterface[] $buttons
     * @param PropertyAccessorInterface|null $propertyAccessor
     * @param ValidatorInterface|null $validator
     */
    public function __construct(Form $form, array $buttons = [], ?PropertyAccessorInterface $propertyAccessor = null, ?ValidatorInterface $validator = null)
    {
        $this->form = $form;
        $this->buttons = $buttons;
        $this->propertyAccessor = $propertyAccessor;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): ElementInterface
    {
        $this->submitButton = null;

        foreach ($this->buttons as $button) {
            if ($button->submit($data) && $this->submitButton === null) {
                $this->submitButton = $button;
            }
        }

        $this->form->submit($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($entity): ElementInterface
    {
        $this->form->import($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value()
    {
        return $this->form->value();
    }

    /**
     * {@inheritdoc}
     */
    public function httpValue()
    {
        return $this->form->httpValue();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->form->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function error(): FormError
    {
        return $this->form->error();
    }

    /**
     * {@inheritdoc}
     */
    public function container(): ?ChildInterface
    {
        return null; // root cannot have a container
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ChildInterface $container): ElementInterface
    {
        throw new BadMethodCallException('Cannot wrap a root element into a container');
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
        $buttons = [];

        foreach ($this->buttons as $button) {
            $buttons[$button->name()] = $button->view($field);
        }

        $view = $this->form->view($field);
        $view->setButtons($buttons);

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    public function submitButton(): ?ButtonInterface
    {
        return $this->submitButton;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator(): ValidatorInterface
    {
        if ($this->validator === null) {
            $this->validator = (new ValidatorBuilder())->getValidator();
        }

        return $this->validator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAccessor(): PropertyAccessorInterface
    {
        if ($this->propertyAccessor === null) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function constraintGroups(): array
    {
        if (!$button = $this->submitButton) {
            return [Constraint::DEFAULT_GROUP];
        }

        return $button->constraintGroups() ?: [Constraint::DEFAULT_GROUP];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): ChildInterface
    {
        return $this->form[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->form[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->form[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->form[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->form->getIterator();
    }
}
