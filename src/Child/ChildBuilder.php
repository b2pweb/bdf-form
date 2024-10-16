<?php

namespace Bdf\Form\Child;

use Bdf\Form\Child\Http\ArrayOffsetHttpFields;
use Bdf\Form\Child\Http\HttpFieldsInterface;
use Bdf\Form\Child\Http\PrefixedHttpFields;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Filter\TrimFilter;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Registry\Registry;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\MagicCallForwarding;
use Bdf\Form\Util\TransformerBuilderTrait;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Base builder for a child
 * If a method cannot be found, it'll be delegate to the element builder
 *
 * <code>
 * $builder->add('element', MyElement::class)
 *     ->getter()->setter()
 *     ->default('foo')
 *     ->depends('bar')
 *     ->satisfy(new MyConstraint(['field' => 'bar']))
 * ;
 * </code>
 *
 * @mixin B
 *
 * @method $this satisfy($constraint, $options = null, bool $append = true)
 * @method $this value($value)
 * @method $this transformer($transformer, bool $append = true)
 * @method $this required($options = null)
 *
 * @template B as ElementBuilderInterface
 * @implements ChildBuilderInterface<B>
 */
class ChildBuilder implements ChildBuilderInterface
{
    use MagicCallForwarding;
    use TransformerBuilderTrait {
        transformer as modelTransformer;
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * The list of input dependencies
     *
     * @var string[]
     */
    private $viewDependencies = [];

    /**
     * @var mixed
     */
    private $default;

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var list<callable(RegistryInterface):\Bdf\Form\Filter\FilterInterface[]>
     */
    private $filtersProviders = [];

    /**
     * @var class-string<ChildInterface>
     */
    private $childClassName = Child::class;

    /**
     * @var list<callable(ChildParameters):void>
     */
    private $parametersConfigurators = [];

    /**
     * @var HttpFieldsInterface|null
     */
    private $fields;

    /**
     * @var ElementBuilderInterface
     * @psalm-var B
     */
    private $elementBuilder;

    /**
     * @var HydratorInterface|null
     */
    private $hydrator;

    /**
     * @var ExtractorInterface|null
     */
    private $extractor;

    /**
     * Add the trim filter
     *
     * @var bool
     */
    private $trim = true;


    /**
     * AbstractChildBuilder constructor.
     *
     * @param string $name The element name
     * @param B $elementBuilder
     * @param RegistryInterface|null $registry
     */
    public function __construct(string $name, ElementBuilderInterface $elementBuilder, ?RegistryInterface $registry = null)
    {
        $this->name = $name;
        $this->elementBuilder = $elementBuilder;
        $this->registry = $registry ?: new Registry();
    }

    /**
     * {@inheritdoc}
     */
    final public function hydrator(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function extractor(ExtractorInterface $extractor)
    {
        $this->extractor = $extractor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function filter($filter, bool $append = true)
    {
        if ($append === true) {
            $this->filters[] = $filter;
        } else {
            array_unshift($this->filters, $filter);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function default($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function depends(string ...$inputNames)
    {
        foreach ($inputNames as $inputName) {
            $this->viewDependencies[$inputName] = $inputName;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addParametersConfigurator(callable $configurator)
    {
        $this->parametersConfigurators[] = $configurator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function buildChild(): ChildInterface
    {
        $parameters = $this->buildParameters();

        foreach ($this->parametersConfigurators as $configurator) {
            $configurator($parameters);
        }

        if ($parameters->child === null) {
            $parameters->child = new $parameters->className(
                $this->name,
                $parameters->element,
                $parameters->fields,
                $parameters->filters,
                $parameters->defaultValue,
                $parameters->hydrator,
                $parameters->extractor,
                $parameters->dependencies,
                $parameters->modelTransformer
            );
        }

        foreach ($parameters->factories as $factory) {
            $parameters->child = $factory($parameters);
        }

        return $parameters->child;
    }

    /**
     * Define extractor using a getter
     * The getter is used by `import()` method, which get the value from the model to fill the form element value
     *
     * Prototypes :
     *   function getter(): this - Add a getter extractor, using the child name as property name
     *   function getter(string $propertyName): this - Add a getter extractor, using $propertyName as property name
     *   function getter(callable $transformer): this - Add a getter extractor, with a value transformer
     *   function getter(?string $propertyName, ?callable $transformer, ?callable $customAccessor): this - Add a getter extractor, with a value transformer and a custom accessor
     *
     * <code>
     * $builder->string('foo')->getter(); // import() from the "foo" property
     * $builder->string('foo')->getter('bar'); // import() from the "bar" property
     *
     * // import() from the "foo" property, and apply a transformer to the value
     * // First parameter is the model property value
     * // Second parameter is the current child instance
     * $builder->string('foo')->getter(function ($value, ChildInterface $input) {
     *     return $this->normalizeFoo($value);
     * });
     *
     * // Same as above, but use the "bar" property instead of "foo"
     * $builder->string('foo')->getter('bar', function ($value, ChildInterface $input) {
     *     return $this->normalizeFoo($value);
     * });
     *
     * // Define a custom accessor
     * // First parameter is the import()'ed entity
     * // Second is always null
     * // Third is the mode : always ExtractorInterface::EXTRACTION
     * // Fourth is the Getter instance
     * $builder->string('foo')->getter(null, null, function ($entity, $_, string $mode, Getter $getter) {
     *     return $entity->myCustomGetter();
     * });
     * </code>
     *
     * @param string|callable|null $propertyName The property name. If null use the child name.
     * @param callable|null $transformer The value transformer (transform model value to input value)
     * @param callable|null $customAccessor Custom getter function. If null use the symfony property accessor
     *
     * @return $this
     *
     * @see Getter
     * @see ChildBuilderInterface::extractor()
     * @see ChildInterface::import()
     */
    final public function getter($propertyName = null, ?callable $transformer = null, ?callable $customAccessor = null): self
    {
        return $this->extractor(new Getter($propertyName, $transformer, $customAccessor));
    }

    /**
     * Define hydrator using a setter
     * The setter is used by `fill()` method, which get the value from the element to fill the entity property
     *
     * Prototypes :
     *   function setter(): this - Add a setter hydrator, using the child name as property name
     *   function setter(string $propertyName): this - Add a setter hydrator, using $propertyName as property name
     *   function setter(callable $transformer): this - Add a setter hydrator, with a value transformer
     *   function setter(?string $propertyName, ?callable $transformer, ?callable $customAccessor): this - Add a setter hydrator, with a value transformer and a custom accessor
     *
     * <code>
     * $builder->string('foo')->setter(); // fill() the "foo" property
     * $builder->string('foo')->setter('bar'); // fill() the "bar" property
     *
     * // fill() the "foo" property, and apply a transformer to the value
     * // First parameter is the model property value
     * // Second parameter is the current child instance
     * $builder->string('foo')->setter(function ($value, ChildInterface $input) {
     *     return $this->parseFoo($value);
     * });
     *
     * // Same as above, but use the "bar" property instead of "foo"
     * $builder->string('foo')->setter('bar', function ($value, ChildInterface $input) {
     *     return $this->parseFoo($value);
     * });
     *
     * // Define a custom accessor
     * // First parameter is the fill()'ed entity
     * // Second is the element value
     * // Third is the mode : always ExtractorInterface::HYDRATION
     * // Fourth is the Setter instance
     * $builder->string('foo')->setter(null, null, function ($entity, $value, string $mode, Setter $setter) {
     *     return $entity->myCustomSetter($value);
     * });
     * </code>
     *
     * @param string|callable|null $propertyName The property name. If null use the child name.
     * @param callable|null $transformer The value transformer (transform input [i.e. http] value to model value)
     * @param callable|null $customAccessor Custom setter function. If null use the symfony property accessor
     *
     * @return $this
     *
     * @see Setter
     * @see ChildBuilderInterface::hydrator()
     * @see ChildInterface::fill()
     */
    final public function setter($propertyName = null, ?callable $transformer = null, ?callable $customAccessor = null): self
    {
        return $this->hydrator(new Setter($propertyName, $transformer, $customAccessor));
    }

    /**
     * Helper method for define both simple getter and setter
     * This method is a shortcut for `$builder->getter($name)->setter($name)`
     *
     * This method does not supports transformer and custom accessor.
     * To define one of them, use the getter() and setter() methods.
     *
     * <code>
     * $builder->string('foo')->getset(); // fill() and import()  the "foo" property
     * $builder->string('foo')->getset('bar'); // fill() and import() the "bar" property
     * </code>
     *
     * @param string|null $propertyName The property name. If null use the child name.
     *
     * @return $this
     *
     * @see Setter
     * @see Getter
     * @see ChildBuilder::getter()
     * @see ChildBuilder::setter()
     *
     * @since 1.5
     */
    final public function getset(?string $propertyName = null): self
    {
        return $this->getter($propertyName)->setter($propertyName);
    }

    /**
     * Define the child class name
     *
     * @param class-string<ChildInterface> $factory The child class name
     *
     * @return $this
     */
    final public function childClassName(string $factory): self
    {
        $this->childClassName = $factory;

        return $this;
    }

    /**
     * Define the child HTTP fields
     *
     * @param HttpFieldsInterface $fields
     *
     * @return $this
     */
    final public function httpFields(HttpFieldsInterface $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Define the child as a prefixed partition of the parent form
     *
     * Note: The child element must be an aggregation element, like an embedded form, or an array element
     *
     * <code>
     * // For HTTP value ['emb_foo' => 'xxx', 'emb_bar' => 'xxx']
     * $builder->embedded('emb', function ($builder) {
     *     $builder->string('foo');
     *     $builder->string('bar');
     * })->prefix();
     *
     * // For HTTP value ['efoo' => 'xxx', 'ebar' => 'xxx']
     * $builder->embedded('emb', function ($builder) {
     *     $builder->string('foo');
     *     $builder->string('bar');
     * })->prefix('e');
     * </code>
     *
     * @param string|null $prefix The HTTP fields prefix. If null, use the name followed by en underscore "_" as prefix.
     *                            The prefix may be an empty string for partitioning the parent form without prefixing embedded names
     *
     * @return $this
     *
     * @see PrefixedHttpFields
     */
    final public function prefix(?string $prefix = null): self
    {
        return $this->httpFields(new PrefixedHttpFields($prefix ?? $this->name.'_'));
    }

    /**
     * Enable or disable trim on input value
     *
     * Note: trim is active by default
     *
     * @param bool $active
     *
     * @return $this
     */
    final public function trim(bool $active = true): self
    {
        $this->trim = $active;

        return $this;
    }

    /**
     * Configure the element builder using a callback
     *
     * <code>
     * $builder->string('foo')->configure(function (StringElementBuilder $builder) {
     *     $builder->length(['min' => 3]);
     * });
     * </code>
     *
     * @param callable(B):void $configurator
     *
     * @return $this
     */
    final public function configure(callable $configurator): self
    {
        $configurator($this->elementBuilder);

        return $this;
    }

    /**
     * Forward call to element builder
     *
     * @param callable|TransformerInterface|DataTransformerInterface $transformer
     * @param bool $append
     * @return $this
     *
     * @see ElementBuilderInterface::transformer()
     */
    public function transformer($transformer, bool $append = true)
    {
        $this->elementBuilder->transformer($transformer, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return B
     */
    final protected function getElementBuilder(): ElementBuilderInterface
    {
        return $this->elementBuilder;
    }

    /**
     * {@inheritdoc}
     */
    final protected function registry(): RegistryInterface
    {
        return $this->registry;
    }

    /**
     * Add a new filter provider
     * The filter provider permit to create a filter during the build of the element transformer
     * So the filter can be configured by the child builder
     *
     * @param callable(RegistryInterface):\Bdf\Form\Filter\FilterInterface[] $provider
     */
    final protected function addFilterProvider(callable $provider): void
    {
        $this->filtersProviders[] = $provider;
    }

    /**
     * Create the child parameters
     *
     * @return ChildParameters
     */
    private function buildParameters(): ChildParameters
    {
        $parameters = new ChildParameters();

        $parameters->name = $this->name;
        $parameters->hydrator = $this->hydrator;
        $parameters->extractor = $this->extractor;
        $parameters->dependencies = $this->viewDependencies;
        $parameters->modelTransformer = $this->buildTransformer();
        $parameters->className = $this->childClassName;

        $parameters->filters = $this->trim ? [TrimFilter::instance()] : [];

        foreach ($this->filters as $filter) {
            $parameters->filters[] = $this->registry->filter($filter);
        }

        foreach ($this->filtersProviders as $provider) {
            $parameters->filters = array_merge($parameters->filters, $provider($this->registry));
        }

        $parameters->fields = $this->fields ?: new ArrayOffsetHttpFields($this->name);
        $parameters->element = $this->elementBuilder->buildElement();

        // Apply element transformation to the default value
        if ($this->default !== null) {
            $lastValue = $parameters->element->value();
            $parameters->defaultValue = $parameters->element->import($this->default)->httpValue();
            $parameters->element->import($lastValue);
        } else {
            $parameters->defaultValue = null;
        }

        return $parameters;
    }
}
