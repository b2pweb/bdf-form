<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Aggregate\View\FormView;
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
 * // Declaration
 * class MyForm extends CustomForm
 * {
 *     public function configure(FormBuilderInterface $builder)
 *     {
 *         $builder->generates(MyEntity::class);
 *         $builder->string('foo')->setter();
 *     }
 * }
 *
 * // Usage
 * $form = new MyForm(); // Directly instantiate the form
 * $form = $this->registry->elementBuilder(MyForm::class)->buildElement(); // Use registry and builder
 *
 * if (!$form->submit($request->post())->valid()) {
 *     return new JsonResponse($form->error()->print(new FormErrorFormat()), 400);
 * }
 *
 * $entity = $form->value();
 * $this->service->handle($entity);
 *
 * return new Response('OK', 200);
 * </code>
 *
 * @todo implements root form interface ?
 * @template T
 * @implements FormInterface<T>
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
     * @var FormInterface<T>|null
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
        $this->submitTarget()->submit($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function patch($data): ElementInterface
    {
        $this->submitTarget()->patch($data);

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
        $form = $this->form();
        /** @var FormView $view */
        $view = $form->container() === null
            ? $form->root()->view($field)
            : $form->view($field)
        ;

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
     * @return FormInterface<T>
     */
    final protected function form(): FormInterface
    {
        if ($this->form) {
            return $this->form;
        }

        $this->configure($this->builder);

        return $this->form = $this->builder->buildElement();
    }

    /**
     * Get the submit target element
     * This element must be used for all submit or patch call
     * Handle submit button if the current form is the root element
     *
     * @return ElementInterface
     */
    final protected function submitTarget(): ElementInterface
    {
        $form = $this->form();

        // The form is the root form
        if ($form->container() === null) {
            return $form->root();
        }

        return $form;
    }
}
