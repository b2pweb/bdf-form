<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Util\DelegateElementBuilderTrait;

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

            return new $className($this->builder);
        }

        return ($this->formFactory)($this->builder);
    }

    /**
     * {@inheritdoc}
     */
    protected function getElementBuilder(): ElementBuilderInterface
    {
        return $this->builder;
    }
}
