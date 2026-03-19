<?php

namespace Bdf\Form\Registry;

use Bdf\Form\Aggregate\ArrayChildBuilder;
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
use Bdf\Form\Leaf\AnyElement;
use Bdf\Form\Leaf\AnyElementBuilder;
use Bdf\Form\Leaf\BackedEnumElement;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\BooleanElementBuilder;
use Bdf\Form\Leaf\Date\DateTimeChildBuilder;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\Date\DateTimeElementBuilder;
use Bdf\Form\Leaf\EnumElementBuilder;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\FloatElementBuilder;
use Bdf\Form\Leaf\Helper\EmailElement;
use Bdf\Form\Leaf\Helper\EmailElementBuilder;
use Bdf\Form\Leaf\Helper\UrlElement;
use Bdf\Form\Leaf\Helper\UrlElementBuilder;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Leaf\UnitEnumElement;
use Bdf\Form\Phone\PhoneChildBuilder;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\Phone\PhoneElementBuilder;
use InvalidArgumentException;
use Override;

use function is_string;
use function is_subclass_of;

/**
 * Base registry interface
 */
final class Registry implements RegistryInterface
{
    /**
     * @var class-string<ElementBuilderInterface>[]|callable[]
     */
    private array $elementBuilderFactories = [
        StringElement::class => StringElementBuilder::class,
        IntegerElement::class => IntegerElementBuilder::class,
        FloatElement::class => FloatElementBuilder::class,
        BooleanElement::class => BooleanElementBuilder::class,
        AnyElement::class => AnyElementBuilder::class,
        BackedEnumElement::class => EnumElementBuilder::class,
        UnitEnumElement::class  => EnumElementBuilder::class,

        EmailElement::class => EmailElementBuilder::class,
        UrlElement::class => UrlElementBuilder::class,

        CsrfElement::class => CsrfElementBuilder::class,
        PhoneElement::class => PhoneElementBuilder::class,

        DateTimeElement::class => DateTimeElementBuilder::class,

        ArrayElement::class => ArrayElementBuilder::class,
        Form::class => FormBuilder::class,
    ];

    /**
     * @var class-string<ChildBuilderInterface>[]|callable[]
     */
    private array $childBuilderFactories = [
        DateTimeElement::class => DateTimeChildBuilder::class,
        PhoneElement::class => PhoneChildBuilder::class,
        ArrayElement::class => ArrayChildBuilder::class,
    ];

    /**
     * Registry constructor.
     */
    public function __construct()
    {
        $this->register(CustomForm::class, function (RegistryInterface $registry, string $formClass) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return new CustomFormBuilder($formClass, $this->elementBuilder(Form::class));
        });
    }

    #[Override]
    public function childBuilder(string $element, string $name): ChildBuilderInterface
    {
        $elementBuilder = $this->elementBuilder($element);

        $builderFactory = $this->childBuilderFactories[$element] ?? ChildBuilder::class;

        if (is_string($builderFactory)) {
            /** @var class-string<ChildBuilderInterface> $builderFactory */
            return new $builderFactory($name, $elementBuilder, $this);
        }

        return $builderFactory($name, $elementBuilder, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param class-string<E> $element
     * @psalm-template E as \Bdf\Form\ElementInterface
     * @psalm-return ElementBuilderInterface<E>
     */
    #[Override]
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

        if ($builderFactory === null) {
            throw new InvalidArgumentException('The element '.$element.' is not registered');
        }

        if (is_string($builderFactory)) {
            /** @var class-string<ElementBuilderInterface<E>> $builderFactory */
            return new $builderFactory($this, $element);
        }

        return ($builderFactory)($this, $element);
    }

    #[Override]
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
     *
     * // Register with a custom child builder
     * $registry->register(MyCustomElement::class, MyCustomBuilder::class, function (string $name, ElementBuilderInterface $builder, Registry $registry) {
     *     return new MyCustomChildBuilder($registry, new ChildBuilder($name, $builder, $registry));
     * });
     * </code>
     *
     * @param string $elementType The element class name
     * @param class-string<ElementBuilderInterface>|callable $builderFactory The builder factory, or builder class name
     * @param class-string<ChildBuilderInterface>|callable|null $childBuilderFactory The builder factory for child, or builder class name. If null, use default child builder
     *
     * @see Registry::elementBuilder()
     */
    public function register(string $elementType, string|callable $builderFactory, string|callable|null $childBuilderFactory = null): void
    {
        $this->elementBuilderFactories[$elementType] = $builderFactory;

        if ($childBuilderFactory !== null) {
            $this->childBuilderFactories[$elementType] = $childBuilderFactory;
        }
    }
}
