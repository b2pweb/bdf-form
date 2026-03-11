<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Aggregate\View\FormView;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Csrf\CsrfValueValidator;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\View\ElementViewInterface;
use Iterator;
use Override;
use WeakReference;

use function method_exists;

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
 * @template T as array|object
 * @implements FormInterface<T>
 */
abstract class CustomForm implements FormInterface
{
    private readonly FormBuilderInterface $builder;

    /**
     * The inner form
     *
     * @var FormInterface<T>|null
     */
    private ?FormInterface $form = null;

    /**
     * @var WeakReference<ChildInterface>|null
     */
    private ?WeakReference $container = null;

    /**
     * @var list<callable(static, FormBuilderInterface): void>
     */
    private array $preConfigureHooks = [];

    /**
     * @var list<callable(static, FormInterface<T>): void>
     */
    private array $postConfigureHooks = [];

    /**
     * CustomForm constructor.
     *
     * @param FormBuilderInterface|null $builder
     */
    public function __construct(?FormBuilderInterface $builder = null)
    {
        $this->builder = $builder ?? new FormBuilder();
    }

    #[Override]
    public function offsetGet(mixed $offset): ChildInterface
    {
        return $this->form()[$offset];
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->form()[$offset]);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->form()[$offset] = $value;
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->form()[$offset]);
    }

    #[Override]
    public function getIterator(): Iterator
    {
        return $this->form()->getIterator();
    }

    #[Override]
    public function submit(mixed $data): static
    {
        $this->submitTarget()->submit($data);

        return $this;
    }

    #[Override]
    public function patch(mixed $data): static
    {
        $this->submitTarget()->patch($data);

        return $this;
    }

    #[Override]
    public function import($entity): static
    {
        $this->form()->import($entity);

        return $this;
    }

    #[Override]
    public function value(): array|object|null
    {
        return $this->form()->value();
    }

    #[Override]
    public function httpValue(): mixed
    {
        return $this->form()->httpValue();
    }

    #[Override]
    public function valid(): bool
    {
        return $this->form()->valid();
    }

    #[Override]
    public function failed(): bool
    {
        // Do not use $this->form()->failed() because it may be not implemented
        return !$this->valid();
    }

    #[Override]
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $this->form()->error($field);
    }

    #[Override]
    public function container(): ?ChildInterface
    {
        return $this->container?->get();
    }

    #[Override]
    public function setContainer(ChildInterface $container): ElementInterface
    {
        $form = clone $this;
        $form->container = WeakReference::create($container);
        $form->form = null; // Reset the form to ensure that $this references will be regenerated

        return $form;
    }

    #[Override]
    public function root(): RootElementInterface
    {
        // @todo bad root form ?
        return $this->form()->root();
    }

    #[Override]
    public function attach(mixed $entity): FormInterface
    {
        $this->form()->attach($entity);

        return $this;
    }

    #[Override]
    public function view(?HttpFieldPath $field = null): FormView
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
     * Disable the CSRF validation
     * The CSRF token will be still generated, and the element will be still present on the form
     *
     * Note: the CSRF will be disabled on the root form, so all the sub-forms will be affected
     *
     * @return void
     */
    final public function disableCsrfValidation(): void
    {
        $this->root()->set(CsrfValueValidator::FLAG_DISABLE_CSRF_VALIDATION, true);
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

        foreach ($this->preConfigureHooks as $hook) {
            $hook($this, $builder);
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->configure($builder);

        /** @var FormInterface<T> $form */
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
