<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Registry\Registry;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Util\TransformerBuilderTrait;
use Bdf\Form\Util\ValidatorBuilderTrait;
use Symfony\Component\Validator\Constraint;
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
 */
class ArrayElementBuilder implements ElementBuilderInterface
{
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
     * @var ElementBuilderInterface
     */
    private $element;

    /**
     * @var mixed
     */
    private $value;


    /**
     * ArrayBuilder constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry = null)
    {
        $this->registry = $registry ?: new Registry();
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
     * @param string $element The element class name
     * @param callable|null $configurator Callback for configure the inner element builder. Takes as parameter the element builder
     *
     * @return $this
     */
    public function element(string $element, ?callable $configurator = null): ArrayElementBuilder
    {
        // @todo exception if already defined ?
        $this->element = $this->registry->elementBuilder($element);

        if ($configurator) {
            $configurator($this->element);
        }

        return $this;
    }

    /**
     * Get the inner element builder
     *
     * @return ElementBuilderInterface
     */
    public function getElementBuilder(): ElementBuilderInterface
    {
        if (!$this->element) {
            $this->element(StringElement::class);
        }

        return $this->element;
    }

    /**
     * Add a new string element on the form
     *
     * @param callable|null $configurator Callback for configure the inner element builder
     *
     * @return $this
     */
    public function string(?callable $configurator = null): ArrayElementBuilder
    {
        return $this->element(StringElement::class, $configurator);
    }

    /**
     * Add a new integer element on the form
     *
     * @param callable|null $configurator Callback for configure the inner element builder
     *
     * @return $this
     */
    public function integer(?callable $configurator = null): ArrayElementBuilder
    {
        return $this->element(IntegerElement::class, $configurator);
    }

    /**
     * Add an embedded form
     *
     * @param callable|null $configurator Configure the embedded form
     *
     * @return $this
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
        if (is_string($options)) {
            $options = ['message' => $options, 'multipleMessage' => $options];
        }

        $options['multiple'] = true;

        return $this->baseChoices($choices, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return ArrayElement
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
     * Forward call to the inner element builder
     *
     * @param string $name
     * @param array $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $this->getElementBuilder()->$name(...$arguments);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function registry(): RegistryInterface
    {
        return $this->registry;
    }
}
