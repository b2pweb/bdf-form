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
use Iterator;
use WeakReference;

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
     * @var WeakReference<ChildInterface>|null
     */
    private $container;

    /**
     * @var list<callable(static, FormBuilderInterface): void>
     */
    private $preConfigureHooks = [];

    /**
     * @var list<callable(static, FormInterface<T>): void>
     */
    private $postConfigureHooks = [];

    /**
     * CustomForm constructor.
     *
     * @param FormBuilderInterface|null $builder
     */
    public function __construct(?FormBuilderInterface $builder = null)
    {
        $this->builder = $builder ?? new FormBuilder();
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
    public function offsetSet($offset, $value): void
    {
        $this->form()[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->form()[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Iterator
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
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $this->form()->error($field);
    }

    /**
     * {@inheritdoc}
     */
    public function container(): ?ChildInterface
    {
        return $this->container ? $this->container->get() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ChildInterface $container): ElementInterface
    {
        $form = clone $this;
        $form->container = WeakReference::create($container);
        $form->form = null; // Reset the form to ensure that $this references will be regenerated

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
     * @param FormBuilder $builder
     */
    abstract protected function configure(FormBuilderInterface $builder): void;

    /**
     * Override this method to hook the inner form build
     *
     * <code>
     * class MyForm extends CustomForm
     * {
     *     public $foo;
     *     public function configure(FormBuilderInterface $builder): void
     *     {
     *         $builder->string('foo');
     *     }
     *
     *     public function postConfigure(FormInterface $form): void
     *     {
     *         // Get the "foo" children
     *         $this->foo = $form['foo'];
     *     }
     * }
     * </code>
     *
     * @param FormInterface $form The inner form built instance
     */
    public function postConfigure(FormInterface $form): void
    {
        // to overrides
    }

    /**
     * Define hooks called before the form is built
     * @param list<callable(static, FormBuilderInterface):void> $hooks
     * @return void
     * @internal This method should be called by the {@see CustomFormBuilder}
     */
    final public function setPreConfigureHooks(array $hooks): void
    {
        $this->preConfigureHooks = $hooks;
    }

    /**
     * Define hooks hook called after the form is built
     * @param list<callable(static, FormInterface<T>):void> $hooks
     * @return void
     * @internal This method should be called by the {@see CustomFormBuilder}
     */
    final public function setPostConfigureHooks(array $hooks): void
    {
        $this->postConfigureHooks = $hooks;
    }

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

        // Form can be rebuilt, so we need to clone the builder to avoid side effects
        $builder = clone $this->builder;

        /** @var static $this Psalm cannot infer this type */

        foreach ($this->preConfigureHooks as $hook) {
            $hook($this, $builder);
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->configure($builder);

        $form = $builder->buildElement();

        if ($this->container && $container = $this->container->get()) {
            $form = $form->setContainer($container);
        }

        $this->form = $form;

        $this->postConfigure($form);

        foreach ($this->postConfigureHooks as $hook) {
            $hook($this, $form);
        }

        return $form;
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
