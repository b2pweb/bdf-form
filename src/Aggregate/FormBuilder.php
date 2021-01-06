<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Value\ValueGenerator;
use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Csrf\CsrfElement;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Builder for a form
 *
 * @see Form
 */
class FormBuilder extends AbstractElementBuilder implements FormBuilderInterface
{
    /**
     * @var ChildBuilderInterface[]
     */
    private $children = [];

    /**
     * @var ButtonBuilderInterface[]
     */
    private $buttons = [];

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ValueGeneratorInterface
     */
    private $generator;


    /**
     * FormBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(?RegistryInterface $registry = null)
    {
        parent::__construct($registry);
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $name, string $element): ChildBuilderInterface
    {
        return $this->children[$name] = $this->registry()->childBuilder($element, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function string(string $name, $default = null): ChildBuilderInterface
    {
        return $this->add($name, StringElement::class)->default($default);
    }

    /**
     * {@inheritdoc}
     */
    public function integer(string $name, $default = null): ChildBuilderInterface
    {
        return $this->add($name, IntegerElement::class)->default($default);
    }

    /**
     * {@inheritdoc}
     */
    public function float(string $name, $default = null): ChildBuilderInterface
    {
        return $this->add($name, FloatElement::class)->default($default);
    }

    /**
     * {@inheritdoc}
     */
    public function boolean(string $name): ChildBuilderInterface
    {
        return $this->add($name, BooleanElement::class);
    }

    /**
     * {@inheritdoc}
     */
    public function dateTime(string $name): ChildBuilderInterface
    {
        return $this->add($name, DateTimeElement::class);
    }

    /**
     * {@inheritdoc}
     */
    public function csrf(string $name = '_token'): ChildBuilderInterface
    {
        return $this->add($name, CsrfElement::class);
    }

    /**
     * {@inheritdoc}
     */
    public function embedded(string $name, ?callable $configurator = null): ChildBuilderInterface
    {
        $builder = $this->add($name, Form::class);

        if ($configurator) {
            $configurator($builder);
        }

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function array(string $name, ?string $elementType = null, ?callable $elementConfigurator = null): ChildBuilderInterface
    {
        $builder = $this->add($name, ArrayElement::class);

        if ($elementType) {
            $builder->element($elementType, $elementConfigurator);
        }

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(string $name): ButtonBuilderInterface
    {
        return $this->buttons[$name] = $this->registry()->buttonBuilder($name);
    }

    /**
     * {@inheritdoc}
     */
    public function propertyAccessor(PropertyAccessorInterface $propertyAccessor): FormBuilderInterface
    {
        $this->propertyAccessor = $propertyAccessor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validator(ValidatorInterface $validator): FormBuilderInterface
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generator(ValueGeneratorInterface $generator): FormBuilderInterface
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generates($entity): FormBuilderInterface
    {
        return $this->generator(new ValueGenerator($entity));
    }

    /**
     * {@inheritdoc}
     */
    final protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        $children = new ChildrenCollection();

        foreach ($this->children as $child) {
            $children->add($child->buildChild());
        }

        $form = new Form($children, $validator, $transformer, $this->generator);

        // The root form is configured by the builder : set into the form
        if ($this->hasRootFormConfiguration()) {
            $form->setRoot($this->buildRootForm($form));
        }

        return $form;
    }

    /**
     * Check if there is at least one attribute of the root form that is configured by the builder
     *
     * @return bool
     */
    private function hasRootFormConfiguration(): bool
    {
        return $this->buttons || $this->validator || $this->propertyAccessor;
    }

    /**
     * Build the root form
     *
     * @param Form $form
     *
     * @return RootElementInterface
     */
    private function buildRootForm(Form $form): RootElementInterface
    {
        $buttons = [];

        foreach ($this->buttons as $button) {
            $buttons[] = $button->buildButton();
        }

        return new RootForm($form, $buttons, $this->propertyAccessor, $this->validator);
    }
}
