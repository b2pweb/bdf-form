<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Choice\ArrayChoice;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Choice\LazzyChoice;
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
    public function __construct(RegistryInterface $registry = null)
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
    public function transformer($transformer, bool $append = true)
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
     * @param class-string<ElementInterface<RT>> $element The element class name
     * @param callable(ElementBuilderInterface<ElementInterface<RT>>):void|null $configurator Callback for configure the inner element builder. Takes as parameter the element builder
     *
     * @template RT
     *
     * @return ArrayElementBuilder<RT>
     */
    public function element(string $element, ?callable $configurator = null): ArrayElementBuilder
    {
        /** @var ArrayElementBuilder<RT> $this */
        // @todo exception if already defined ?
        $this->element = $this->registry->elementBuilder($element);

        if ($configurator) {
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
     * @param callable(ElementBuilderInterface<ElementInterface<string>>):void|null $configurator Callback for configure the inner element builder
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
     * @param callable(ElementBuilderInterface<ElementInterface<int>>):void|null $configurator Callback for configure the inner element builder
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
     * @param callable(ElementBuilderInterface<ElementInterface<float>>):void|null $configurator Callback for configure the inner element builder
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
     * @param callable(ElementBuilderInterface<ElementInterface<bool>>):void|null $configurator Callback for configure the inner element builder
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
     * @param array $options Constraint options. Keys are "min", "max"
     *
     * @return $this
     *
     * @see Count For the list of options
     */
    public function count(array $options): ArrayElementBuilder
    {
        return $this->arrayConstraint(new Count($options));
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    final public function required($options = null)
    {
        if (!$options instanceof Constraint) {
            if (is_string($options)) {
                $options = ['message' => $options];
            }

            $options = new NotBlank($options);
        }

        return $this->arrayConstraint($options);
    }

    /**
     * {@inheritdoc}
     */
    final public function choices($choices, $options = null): self
    {
        // @fixme c/c choice from trait
        if (!$choices instanceof ChoiceInterface) {
            $choices = is_array($choices) ? new ArrayChoice($choices) : new LazzyChoice($choices);
        }

        if (is_string($options)) {
            $options = ['message' => $options, 'multipleMessage' => $options];
        }

        $options['callback'] = [$choices, 'values'];
        $options['multiple'] = true;

        $this->choices = $choices;

        return $this->arrayConstraint(new ChoiceConstraint($options));
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
