<?php

namespace Bdf\Form\Aggregate;

use BackedEnum;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\AnyElement;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\EnumElementBuilder;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\UnitEnumElement;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\Registry\Registry;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Struct\StructForm;
use Bdf\Form\Struct\StructFormBuilder;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\MagicCallForwarding;
use Bdf\Form\Util\TransformerBuilderTrait;
use Bdf\Form\Util\ValidatorBuilderTrait;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice as ChoiceConstraint;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use UnitEnum;

use function assert;

/**
 * Builder for the array element
 *
 * <code>
 * $builder->array('names')->string()
 *     ->length(['min' => 3]) // Add "length" constraint to inner string
 *     ->count(['min' => 1, 'max' => 6]) // Add count constraint
 *     ->satisfyArray(new MyArrayConstraint()) // Add a constraint for the array
 * ;
 * </code>
 *
 * @see ArrayElement
 * @see FormBuilderInterface::array()
 *
 * @template T
 * @implements ElementBuilderInterface<ArrayElement<T>>
 */
class ArrayElementBuilder implements ElementBuilderInterface
{
    use MagicCallForwarding;

    use ChoiceBuilderTrait {
        ChoiceBuilderTrait::choices as protected baseChoices;
    }

    use TransformerBuilderTrait {
        transformer as arrayTransformer;
    }

    use ValidatorBuilderTrait {
        ValidatorBuilderTrait::satisfy as arrayConstraint;
    }

    private readonly RegistryInterface $registry;

    /**
     * @var ElementBuilderInterface<ElementInterface<T>>|null
     */
    private ?ElementBuilderInterface $element = null;
    private mixed $value = null;

    /**
     * ArrayBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(?RegistryInterface $registry = null)
    {
        $this->registry = $registry ?? new Registry();
    }

    /**
     * {@inheritdoc}
     *
     * Define a constraint on the inner element
     */
    #[Override]
    public function satisfy(Constraint|callable $constraint, ?string $message = null, bool $append = true): static
    {
        $this->getElementBuilder()->satisfy($constraint, $message, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Define a transformer on the inner element
     */
    #[Override]
    public function transformer(callable|TransformerInterface $transformer, bool $append = true): static
    {
        $this->getElementBuilder()->transformer($transformer, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function value($value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Define the inner element
     *
     * <code>
     * $builder->array('phones')->element(PhoneElement::class, function (PhoneElementBuilder $builder) {
     *     $builder->regionInput('../../address/country');
     * });
     * </code>
     *
     * @param class-string<E> $element The element class name
     * @param callable(EB):void|null $configurator Callback for configure the inner element builder. Takes as parameter the element builder
     *
     * @template RT
     * @template E as ElementInterface<RT>
     * @template EB as ElementBuilderInterface<E>
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<RT>
     */
    public function element(string $element, ?callable $configurator = null): static
    {
        // @todo exception if already defined ?
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->element = $this->registry->elementBuilder($element);

        if ($configurator !== null) {
            /** @psalm-suppress InvalidArgument */
            $configurator($this->element);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ElementBuilderInterface<ElementInterface<T>>
     */
    #[Override]
    public function getElementBuilder(): ElementBuilderInterface
    {
        if (!$this->element) {
            $this->element(StringElement::class);
        }

        assert($this->element !== null);

        /** @var ElementBuilderInterface<ElementInterface<T>> */
        return $this->element;
    }

    /**
     * Define as array of string
     *
     * <code>
     * $builder->array('names')->string(function (StringElementBuilder $builder) {
     *     $builder->length(['min' => 3, 'max' => 32])->regex('/[a-z -]+/i');
     * });
     * </code>
     *
     * @param callable(ElementBuilderInterface<StringElement>):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<string>
     */
    public function string(?callable $configurator = null): static
    {
        return $this->element(StringElement::class, $configurator);
    }

    /**
     * Define as array of integer
     *
     * <code>
     * $builder->array('ids')->integer(function (IntegerElementBuilder $builder) {
     *     $builder->min(1)->max(9999);
     * });
     * </code>
     *
     * @param callable(ElementBuilderInterface<IntegerElement>):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<int>
     */
    public function integer(?callable $configurator = null): static
    {
        return $this->element(IntegerElement::class, $configurator);
    }

    /**
     * Define as array of float
     *
     * <code>
     * $builder->array('prices')->float(function (FloatElementBuilder $builder) {
     *     $builder->min(0.01)->scale(2);
     * });
     * </code>
     *
     * @param callable(ElementBuilderInterface<FloatElement>):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<float>
     */
    public function float(?callable $configurator = null): static
    {
        return $this->element(FloatElement::class, $configurator);
    }

    /**
     * Define as array of boolean
     *
     * <code>
     * $builder->array('flags')->boolean();
     * </code>
     *
     * @param callable(ElementBuilderInterface<BooleanElement>):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<bool>
     */
    public function boolean(?callable $configurator = null): static
    {
        return $this->element(BooleanElement::class, $configurator);
    }

    /**
     * Define as array of date time
     *
     * <code>
     * $builder->array('dates')->dateTime(function (DateTimeElementBuilder $builder) {
     *     $builder->after(new DateTime());
     * });
     * </code>
     *
     * @param callable(ElementBuilderInterface<ElementInterface<\DateTimeInterface>>):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<\DateTimeInterface>
     */
    public function dateTime(?callable $configurator = null): static
    {
        return $this->element(DateTimeElement::class, $configurator);
    }

    /**
     * Define as array of phone number
     *
     * <code>
     * $builder->array('phones')->phone(function (PhoneElementBuilder $builder) {
     *     $builder->regionInput('../../address/country');
     * });
     * </code>
     *
     * @param callable(ElementBuilderInterface<ElementInterface<\libphonenumber\PhoneNumber>>):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<\libphonenumber\PhoneNumber>
     */
    public function phone(?callable $configurator = null): static
    {
        return $this->element(PhoneElement::class, $configurator);
    }

    /**
     * Define as array of any values
     *
     * <code>
     * $builder->array('values')->any();
     * </code>
     *
     * @param callable(ElementBuilderInterface<ElementInterface<mixed>>):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<mixed>
     *
     * @since 2.0
     */
    public function any(?callable $configurator = null): static
    {
        return $this->element(AnyElement::class, $configurator);
    }

    /**
     * Define as array of enum
     *
     * <code>
     * $builder->array('types')->enum(Types::class, function(EnumElementBuilder $builder) {
     *     $builder->backed(false);
     * })->getset();
     * </code>
     *
     * @param class-string<UnitEnum> $enumClass The enum class
     * @param callable(EnumElementBuilder):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<\UnitEnum>
     *
     * @since 2.0
     */
    public function enum(string $enumClass, ?callable $configurator = null): static
    {
        return $this->element(UnitEnumElement::class, function (EnumElementBuilder $builder) use ($enumClass, $configurator) {
            $builder->enumClass($enumClass);

            if ($configurator !== null) {
                $configurator($builder);
            }
        });
    }

    /**
     * Define as array of struct
     *
     * <code>
     * $builder->array('coordinates')->struct(Coordinate::class, function(StructFormBuilder $builder) {
     *     // ...
     * })->getset();
     * </code>
     *
     * @param class-string<S> $className The struct class name
     * @param callable(StructFormBuilder):void|null $configurator Callback for configure the inner element builder
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<S>
     *
     * @template S as object
     * @since 2.0
     */
    public function struct(string $className, ?callable $configurator = null): static
    {
        return $this->element(StructForm::class, function (StructFormBuilder $builder) use ($className, $configurator) {
            $builder->class($className);

            if ($configurator !== null) {
                $configurator($builder);
            }
        });
    }

    /**
     * Define as array of embedded forms
     *
     * <code>
     * $builder->array('addresses')->form(function (FormBuilder $builder) {
     *     $builder->string('address');
     *     $builder->string('city');
     *     $builder->string('zipcode');
     *     $builder->string('country');
     * });
     * </code>
     *
     * @param callable|null $configurator Configure the embedded form
     *
     * @return static
     * @psalm-this-out ArrayElementBuilder<mixed>
     */
    public function form(?callable $configurator = null): static
    {
        return $this->element(Form::class, $configurator);
    }

    /**
     * Add a count constraint on the array
     *
     * Ex: `$builder->count(['min' => 3, 'max' => 5])`
     *
     * @param positive-int|null $exactly The exact expected number of elements
     * @param non-negative-int|null $min Minimum expected number of elements
     * @param positive-int|null $max Maximum expected number of elements
     * @param string|null $exactMessage
     * @param string|null $minMessage
     * @param string|null $maxMessage
     *
     * @return $this
     *
     * @see Count For the list of options
     */
    public function count(?int $exactly = null, ?int $min = null, ?int $max = null, ?string $exactMessage = null, ?string  $minMessage = null, ?string $maxMessage = null): static
    {
        return $this->arrayConstraint(
            new Count(
                exactly: $exactly,
                min: $min,
                max: $max,
                exactMessage: $exactMessage,
                minMessage: $minMessage,
                maxMessage: $maxMessage
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    final public function required(string|Constraint|null $message = null, ?bool $allowNull = null, ?callable $normalizer = null): static
    {
        if (!$message instanceof Constraint) {
            $message = new NotBlank(
                message: $message,
                allowNull: $allowNull,
                normalizer: $normalizer
            );
        }

        return $this->arrayConstraint($message);
    }

    /**
     * {@inheritdoc}
     *
     * @param ChoiceInterface|array|class-string<BackedEnum>|callable $choices The allowed values in PHP form.
     * @param string|null $message The error message.
     * @param non-negative-int $min
     * @param positive-int $max
     */
    final public function choices(ChoiceInterface|array|string|callable $choices, ?string $message = null, ?bool $multiple = null, ?bool $strict = null, ?int $min = null, ?int $max = null, ?string $minMessage = null, ?string $maxMessage = null): static
    {
        /** @psalm-suppress PropertyNotSetInConstructor */
        $builder = new class($this->registry) {
            use ChoiceBuilderTrait {
                getChoices as public;
            }

            public ChoiceConstraint $constraint;

            public function __construct(
                private readonly RegistryInterface $registry,
            ) {}

            #[Override]
            public function satisfy(Constraint|callable $constraint, ?string $message = null, bool $append = true): static
            {
                assert($constraint instanceof ChoiceConstraint);
                $this->constraint = $constraint;
                return $this;
            }

            #[Override]
            protected function registry(): RegistryInterface
            {
                return $this->registry;
            }
        };

        // Force the multiple option to true
        $builder->choices(
            choices: $choices,
            message: $message,
            multiple: true,
            strict: $strict,
            min: $min,
            max: $max,
            minMessage: $minMessage,
            maxMessage: $maxMessage
        );

        $this->arrayConstraint($builder->constraint);
        $this->choices = $builder->getChoices();

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ArrayElement<T>
     */
    #[Override]
    public function buildElement(): ArrayElement
    {
        $element = new ArrayElement(
            $this->getElementBuilder()->buildElement(),
            $this->buildTransformer(),
            $this->buildValidator(),
            $this->getChoices()
        );

        if ($this->value) {
            $element->import($this->value);
        }

        return $element;
    }

    #[Override]
    protected function registry(): RegistryInterface
    {
        return $this->registry;
    }
}
