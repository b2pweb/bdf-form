<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Util\DelegateElementBuilderTrait;

use function is_string;

/**
 * Builder for extends a custom form
 * All build calls are forwarded to the inner form builder
 * The inner builder is used as custom form's builder on the `configure()` method
 *
 * <code>
 * $embedded = $builder->add('embd', MyCustomForm::class);
 * $embedded->string('foo'); // Add a new field
 * </code>
 *
 * @see FormBuilderInterface::add() With custom form class name as second parameter
 * @see RegistryInterface::elementBuilder() With custom form class name as parameter
 * @see CustomForm::configure()
 *
 * @mixin FormBuilderInterface
 */
class CustomFormBuilder implements ElementBuilderInterface
{
    use DelegateElementBuilderTrait;

    /**
     * @var FormBuilderInterface
     */
    private $builder;

    /**
     * @var class-string<CustomForm>|callable(FormBuilderInterface):CustomForm
     */
    private $formFactory;

    /**
     * @var list<callable(CustomForm, FormBuilderInterface): void>
     */
    private $preConfigureHooks = [];

    /**
     * @var list<callable(CustomForm, FormInterface): void>
     */
    private $postConfigureHooks = [];


    /**
     * CustomFormBuilder constructor.
     *
     * @param class-string<CustomForm>|callable(FormBuilderInterface):CustomForm $formFactory
     * @param FormBuilderInterface|null $builder
     */
    public function __construct($formFactory, ?FormBuilderInterface $builder = null)
    {
        $this->formFactory = $formFactory;
        $this->builder = $builder ?: new FormBuilder();
    }

    /**
     * {@inheritdoc}
     *
     * @return CustomForm
     */
    public function buildElement(): ElementInterface
    {
        if (is_string($this->formFactory)) {
            /** @var class-string<CustomForm> $className */
            $className = $this->formFactory;

            $form = new $className($this->builder);
        } else {
            $form = ($this->formFactory)($this->builder);
        }

        $form->setPreConfigureHooks($this->preConfigureHooks);
        $form->setPostConfigureHooks($this->postConfigureHooks);

        return $form;
    }

    /**
     * Add a hook called before the form is built
     *
     * This hook can be used to call setters on the custom form, or to add fields on the inner form builder.
     * It will be called before the {@see CustomForm::configure()} method, and takes as parameter the custom form instance and the inner form builder.
     *
     * Usage:
     * <code>
     * $builder->preConfigure(function (MyCustomForm $form, FormBuilderInterface $builder) {
     *    $form->setFoo('bar');
     *    $builder->string('foo');
     * });
     * </code>
     *
     * @param callable(CustomForm, FormBuilderInterface):void $hook
     *
     * @return $this
     */
    public function preConfigure(callable $hook): self
    {
        $this->preConfigureHooks[] = $hook;

        return $this;
    }

    /**
     * Add a hook called after the form is built
     * It will be called after the {@see CustomForm::configure()} and {@see CustomForm::postConfigure()} methods, and takes as parameter the custom form instance and the inner form.
     *
     * @param callable(CustomForm, FormInterface):void $hook
     *
     * @return $this
     */
    public function postConfigure(callable $hook): self
    {
        $this->postConfigureHooks[] = $hook;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getElementBuilder(): ElementBuilderInterface
    {
        return $this->builder;
    }
}
