<?php

namespace Bdf\Form\Registry;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Button\SubmitButtonBuilder;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Csrf\CsrfElement;
use Bdf\Form\Csrf\CsrfElementBuilder;
use Bdf\Form\Custom\CustomForm;
use Bdf\Form\Custom\CustomFormBuilder;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Filter\ClosureFilter;
use Bdf\Form\Filter\FilterInterface;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\BooleanElementBuilder;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\Date\DateTimeElementBuilder;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\FloatElementBuilder;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\Phone\PhoneElementBuilder;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\DataTransformerAdapter;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Validator\Constraints\Closure;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Base registry interface
 */
final class Registry implements RegistryInterface
{
    /**
     * @var string[]|callable[]
     */
    private $elementBuilderFactories = [
        StringElement::class => StringElementBuilder::class,
        IntegerElement::class => IntegerElementBuilder::class,
        FloatElement::class => FloatElementBuilder::class,
        BooleanElement::class => BooleanElementBuilder::class,

        CsrfElement::class => CsrfElementBuilder::class,
        PhoneElement::class => PhoneElementBuilder::class,

        DateTimeElement::class => DateTimeElementBuilder::class,

        ArrayElement::class => ArrayElementBuilder::class,
        Form::class => FormBuilder::class,
    ];


    /**
     * Registry constructor.
     */
    public function __construct()
    {
        $this->register(CustomForm::class, function (RegistryInterface $registry, string $formClass) {
            return new CustomFormBuilder($formClass, $this->elementBuilder(Form::class));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function filter($filter): FilterInterface
    {
        if ($filter instanceof FilterInterface) {
            return $filter;
        }

        if (is_callable($filter)) {
            return new ClosureFilter($filter);
        }

        // @todo container ?
        return new $filter();
    }

    /**
     * {@inheritdoc}
     */
    public function constraint($constraint): Constraint
    {
        if ($constraint instanceof Constraint) {
            return $constraint;
        }

        if (is_callable($constraint)) {
            return new Closure(['callback' => $constraint]);
        }

        if (is_array($constraint)) {
            $options = $constraint[1];
            $constraint = $constraint[0];

            if (is_string($options)) {
                $options = ['message' => $options];
            }

            return new $constraint($options);
        }

        return new $constraint();
    }

    /**
     * {@inheritdoc}
     */
    public function transformer($transformer): TransformerInterface
    {
        if ($transformer instanceof TransformerInterface) {
            return $transformer;
        }

        if ($transformer instanceof DataTransformerInterface) {
            return new DataTransformerAdapter($transformer);
        }

        if (is_callable($transformer)) {
            return new ClosureTransformer($transformer);
        }

        throw new LogicException('Invalid view transformer given for input '.var_export($transformer, true));
    }

    /**
     * {@inheritdoc}
     */
    public function childBuilder(string $element, string $name): ChildBuilderInterface
    {
        return new ChildBuilder($name, $this->elementBuilder($element), $this);
    }

    /**
     * {@inheritdoc}
     */
    public function elementBuilder(string $element): ElementBuilderInterface
    {
        $builderFactory = null;

        if (isset($this->elementBuilderFactories[$element])) {
            $builderFactory = $this->elementBuilderFactories[$element];
        } else {
            foreach ($this->elementBuilderFactories as $builderElement => $factory) {
                if (is_subclass_of($element, $builderElement, true)) {
                    $builderFactory = $factory;
                    break;
                }
            }
        }

        if (!$builderFactory) {
            throw new InvalidArgumentException('The element '.$element.' is not registered');
        }

        if (is_string($builderFactory)) {
            return new $builderFactory($this, $element);
        }

        return ($builderFactory)($this, $element);
    }

    /**
     * {@inheritdoc}
     */
    public function buttonBuilder(string $name): ButtonBuilderInterface
    {
        return new SubmitButtonBuilder($name);
    }

    /**
     * Register a new element builder
     *
     * <code>
     * // Register MyCustomBuilder as builder for MyCustomElement
     * $registry->register(MyCustomElement::class, MyCustomBuilder::class);
     *
     * // Register a factory builder. The factory takes as parameters the registry, and the element class name
     * $registry->register(MyCustomElement::class, function (Registry $registry, string $element) {
     *     return new MyCustomBuilder($registry);
     * });
     * </code>
     *
     * @param string $elementType The element class name
     * @param string|callable $builderFactory The builder factory, or builder class name
     *
     * @see Registry::elementBuilder()
     */
    public function register(string $elementType, $builderFactory): void
    {
        $this->elementBuilderFactories[$elementType] = $builderFactory;
    }
}
