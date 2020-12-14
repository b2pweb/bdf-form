<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\View\ElementViewInterface;

/**
 * Utility class for simply create a custom form element
 *
 * <code>
 * class MyForm extends CustomForm
 * {
 *     public function configure(FormBuilderInterface $builder)
 *     {
 *         $builder->generates(MyEntity::class);
 *         $builder->string('foo')->setter();
 *     }
 * }
 * </code>
 *
 * @todo delegate to root for submit view etc...
 */
abstract class CustomForm implements FormInterface
{
    /**
     * @var FormBuilderInterface
     */
    private $builder;

    /**
     * The inner form
     *
     * @var FormInterface
     */
    private $form;


    /**
     * CustomForm constructor.
     *
     * @param FormBuilderInterface|null $builder
     */
    public function __construct(?FormBuilderInterface $builder = null)
    {
        $this->builder = $builder ?: new FormBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): ChildInterface
    {
        return $this->form()[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->form()[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->form()[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->form()[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->form()->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): ElementInterface
    {
        $this->form()->submit($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($entity): ElementInterface
    {
        $this->form()->import($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value()
    {
        return $this->form()->value();
    }

    /**
     * {@inheritdoc}
     */
    public function httpValue()
    {
        return $this->form()->httpValue();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->form()->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function error(): FormError
    {
        return $this->form()->error();
    }

    /**
     * {@inheritdoc}
     */
    public function container(): ?ChildInterface
    {
        return $this->form()->container();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ChildInterface $container): ElementInterface
    {
        $form = clone $this;
        $form->form = $this->form()->setContainer($container);

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function root(): RootElementInterface
    {
        // @todo bad root form ?
        return $this->form()->root();
    }

    /**
     * {@inheritdoc}
     */
    public function attach($entity): FormInterface
    {
        $this->form()->attach($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        $view = $this->form()->view($field);

        $view->setType(static::class);

        return $view;
    }

    /**
     * Configure the form using the builder
     *
     * @param FormBuilderInterface $builder
     */
    abstract protected function configure(FormBuilderInterface $builder): void;

    /**
     * Get (or build) the inner form
     *
     * @return FormInterface
     */
    final protected function form(): FormInterface
    {
        if ($this->form) {
            return $this->form;
        }

        $this->configure($this->builder);

        return $this->form = $this->builder->buildElement();
    }
}
