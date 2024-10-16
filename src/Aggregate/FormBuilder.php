<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Value\ValueGenerator;
use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Csrf\CsrfElement;
use Bdf\Form\Csrf\CsrfElementBuilder;
use Bdf\Form\Custom\CustomForm;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\AnyElement;
use Bdf\Form\Leaf\AnyElementBuilder;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\BooleanElementBuilder;
use Bdf\Form\Leaf\Date\DateTimeChildBuilder;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\Date\DateTimeElementBuilder;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\FloatElementBuilder;
use Bdf\Form\Leaf\Helper\EmailElement;
use Bdf\Form\Leaf\Helper\UrlElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Phone\PhoneChildBuilder;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\Phone\PhoneElementBuilder;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Builder for a form
 *
 * <code>
 * // Get the builder
 * $builder = $registry->elementBuilder(Form::class);
 * $builder->generates(MyEntity::class); // Define the generated entity
 *
 * // Declare fields
 * $builder->string('foo')->required()->setter();
 * $builder->integer('bar')->min(11)->required()->setter();
 *
 * // Build the form
 * $form = $builder->buildElement();
 *
 * // Submit and validate http data
 * if (!$form->submit($request->post())->valid()) {
 *     throw new FormError();
 * }
 *
 * // Get the generated entity, and save it
 * $repository->save($form->value());
 * </code>
 *
 * @see Form
 * @see CustomForm::configure()
 *
 * @extends AbstractElementBuilder<Form>
 */
class FormBuilder extends AbstractElementBuilder implements FormBuilderInterface
{
    /**
     * @var array<non-empty-string, ChildBuilder>
     */
    private $children = [];

    /**
     * @var array<non-empty-string, ButtonBuilderInterface>
     */
    private $buttons = [];

    /**
     * @var PropertyAccessorInterface|null
     */
    private $propertyAccessor;

    /**
     * @var ValidatorInterface|null
     */
    private $validator;

    /**
     * @var ValueGeneratorInterface|null
     */
    private $generator;

    /**
     * @var bool
     */
    private $optional = false;


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
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<E> $element
     *
     * @template E as ElementInterface
     *
     * @psalm-return ChildBuilder<ElementBuilderInterface<E>>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress PropertyTypeCoercion
     */
    public function add(string $name, string $element): ChildBuilderInterface
    {
        return $this->children[$name] = $this->registry()->childBuilder($element, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-return ChildBuilder<AnyElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function any(string $name): ChildBuilderInterface
    {
        return $this->add($name, AnyElement::class);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-return ChildBuilder<StringElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function string(string $name, ?string $default = null): ChildBuilderInterface
    {
        return $this->add($name, StringElement::class)->default($default);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-return ChildBuilder<IntegerElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function integer(string $name, ?int $default = null): ChildBuilderInterface
    {
        return $this->add($name, IntegerElement::class)->default($default);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-return ChildBuilder<FloatElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function float(string $name, ?float $default = null): ChildBuilderInterface
    {
        return $this->add($name, FloatElement::class)->default($default);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-return ChildBuilder<BooleanElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function boolean(string $name): ChildBuilderInterface
    {
        return $this->add($name, BooleanElement::class);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-return DateTimeChildBuilder&ChildBuilder<DateTimeElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function dateTime(string $name): ChildBuilderInterface
    {
        return $this->add($name, DateTimeElement::class);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-return PhoneChildBuilder&ChildBuilder<PhoneElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function phone(string $name): ChildBuilderInterface
    {
        return $this->add($name, PhoneElement::class);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-return ChildBuilder<CsrfElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function csrf(string $name = '_token'): ChildBuilderInterface
    {
        return $this->add($name, CsrfElement::class);
    }

    /**
     * Add a new email element
     * Note: The email element is a simple string but with the email constraint
     *
     * <code>
     * $builder->email('contact')
     *     ->message('Invalid contact email')
     * ;
     * </code>
     *
     * @param non-empty-string $name The name of the input
     *
     * @return ChildBuilder|\Bdf\Form\Leaf\Helper\EmailElementBuilder
     * @psalm-return ChildBuilder<\Bdf\Form\Leaf\Helper\EmailElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function email(string $name): ChildBuilderInterface
    {
        return $this->add($name, EmailElement::class);
    }

    /**
     * Add a new url element
     * Note: The url element is a simple string but with the url constraint
     *
     * <code>
     * $builder->url('home')->protocols('https');
     * </code>
     *
     * @param non-empty-string $name The name of the input
     *
     * @return ChildBuilder|\Bdf\Form\Leaf\Helper\UrlElementBuilder
     * @psalm-return ChildBuilder<\Bdf\Form\Leaf\Helper\UrlElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function url(string $name): ChildBuilderInterface
    {
        return $this->add($name, UrlElement::class);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param non-empty-string $name
     * @psalm-param callable|null $configurator
     * @psalm-return ChildBuilder<FormBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
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
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<ElementInterface>|null $elementType
     * @psalm-param callable|null $elementConfigurator
     * @psalm-return ArrayChildBuilder&ChildBuilderInterface<ArrayElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function array(string $name, ?string $elementType = null, ?callable $elementConfigurator = null): ChildBuilderInterface
    {
        /** @var ChildBuilderInterface<ArrayElementBuilder>&ArrayChildBuilder $builder */
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
    public function optional(bool $flag = true): FormBuilderInterface
    {
        $this->optional = $flag;

        return $this;
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

        $form = new Form($children, $validator, $transformer, $this->generator, $this->optional);

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

        foreach ($this->buttons as $name => $button) {
            $buttons[$name] = $button->buildButton();
        }

        return new RootForm($form, $buttons, $this->propertyAccessor, $this->validator);
    }
}
