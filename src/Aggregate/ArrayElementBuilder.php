<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\Registry\Registry;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\MagicCallForwarding;
use Bdf\Form\Util\TransformerBuilderTrait;
use Bdf\Form\Util\ValidatorBuilderTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice as ChoiceConstraint;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

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

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var ElementBuilderInterface<ElementInterface<T>>|null
     */
    private $element;

    /**
     * @var mixed
     */
    private $value;


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
    public function satisfy($constraint, $options = null, bool $append = true)
    {
        $this->getElementBuilder()->satisfy($constraint, $options, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Define a transformer on the inner element
     */
    public function transformer(callable|TransformerInterface $transformer, bool $append = true)
    {
        $this->getElementBuilder()->transformer($transformer, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value($value)
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
     * @return ArrayElementBuilder<RT>
     */
    public function element(string $element, ?callable $configurator = null): ArrayElementBuilder
    {
        /** @var ArrayElementBuilder<RT> $this */
        // @todo exception if already defined ?
        $this->element = $this->registry->elementBuilder($element);

        if ($configurator) {
            /** @psalm-suppress InvalidArgument */
            $configurator($this->element);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ElementBuilderInterface<ElementInterface<T>>
     * @psalm-suppress InvalidNullableReturnType
     */
    public function getElementBuilder(): ElementBuilderInterface
    {
        if (!$this->element) {
            $this->element(StringElement::class);
        }

        /** @psalm-suppress NullableReturnStatement */
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
     * @return ArrayElementBuilder<string>
     */
    public function string(?callable $configurator = null): ArrayElementBuilder
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
     * @return ArrayElementBuilder<int>
     */
    public function integer(?callable $configurator = null): ArrayElementBuilder
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
     * @return ArrayElementBuilder<float>
     */
    public function float(?callable $configurator = null): ArrayElementBuilder
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
     * @return ArrayElementBuilder<bool>
     */
    public function boolean(?callable $configurator = null): ArrayElementBuilder
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
     * @return ArrayElementBuilder<\DateTimeInterface>
     */
    public function dateTime(?callable $configurator = null): ArrayElementBuilder
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
     * @return ArrayElementBuilder<\libphonenumber\PhoneNumber>
     */
    public function phone(?callable $configurator = null): ArrayElementBuilder
    {
        return $this->element(PhoneElement::class, $configurator);
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
     * @return ArrayElementBuilder<mixed>
     */
    public function form(?callable $configurator = null): ArrayElementBuilder
    {
        return $this->element(Form::class, $configurator);
    }

    /**
     * Add a count constraint on the array
     *
     * Ex: `$builder->count(['min' => 3, 'max' => 5])`
     *
     * @param int|null $exactly The exact expected number of elements
     * @param int|null $min Minimum expected number of elements
     * @param int|null $max Maximum expected number of elements
     * @param string|null $exactMessage
     * @param string|null $minMessage
     * @param string|null $maxMessage
     *
     * @return $this
     *
     * @see Count For the list of options
     */
    public function count(?int $exactly = null, ?int $min = null, ?int $max = null, ?string $exactMessage = null, ?string  $minMessage = null, ?string $maxMessage = null): ArrayElementBuilder
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
    final public function required(string|Constraint|null $message = null, ?bool $allowNull = null, ?callable $normalizer = null)
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
     */
    final public function choices($choices, $message = null, ?bool $multiple = null, ?bool $strict = null, ?int $min = null, ?int $max = null, ?string $minMessage = null, ?string $maxMessage = null): self
    {
        $builder = new class {
            use ChoiceBuilderTrait {
                getChoices as public;
            }

            public ChoiceConstraint $constraint;

            public function satisfy($constraint, $options = null, bool $append = true)
            {
                $this->constraint = $constraint;
                return $this;
            }
        };

        // Force the multiple option to true
        $builder->choices($choices, $message, true, $strict, $min, $max, $minMessage, $maxMessage);

        $this->arrayConstraint($builder->constraint);
        $this->choices = $builder->getChoices();

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ArrayElement<T>
     */
    public function buildElement(): ElementInterface
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

    /**
     * {@inheritdoc}
     */
    protected function registry(): RegistryInterface
    {
        return $this->registry;
    }
}
