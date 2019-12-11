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

/**
 * Base builder for a child
 * If a method cannot be found, it'll be delegate to the element builder
 *
 * @method $this satisfy($constraint, $options = null, $append = true)
 * @method $this value($value)
 * @method $this transformer($transformer, $append = true)
 * @method $this required($options = null)
 */
class ChildBuilder implements ChildBuilderInterface
{
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
     * @var ChildCreationStrategyInterface|callable|string
     */
    private $factory = Child::class;

    /**
     * @var HttpFieldsInterface
     */
    private $fields;

    /**
     * @var ElementBuilderInterface
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
     * @param string $name
     * @param ElementBuilderInterface $elementBuilder
     * @param RegistryInterface $registry
     */
    public function __construct(string $name, ElementBuilderInterface $elementBuilder, RegistryInterface $registry = null)
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
    final public function filter($filter, $append = true)
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
    final public function buildChild(): ChildInterface
    {
        $filters = array_map([$this->registry, 'filter'], $this->filters);

        if ($this->trim) {
            $filters[] = new TrimFilter();
        }

        $fields = $this->fields ?: new ArrayOffsetHttpFields($this->name);

        if (is_string($this->factory)) {
            return new $this->factory(
                $this->name,
                $this->elementBuilder->buildElement(),
                $fields,
                $filters,
                $this->default,
                $this->hydrator,
                $this->extractor,
                $this->viewDependencies
            );
        }

        return ($this->factory)(
            $this->name,
            $this->elementBuilder->buildElement(),
            $fields,
            $filters,
            $this->default,
            $this->hydrator,
            $this->extractor,
            $this->viewDependencies
        );
    }

    /**
     * Define extractor using a getter
     *
     * Prototypes :
     *   function getter(): this - Add a getter extractor, using the child name as property name
     *   function getter(string $propertyName): this - Add a getter extractor, using $propertyName as property name
     *   function getter(callable $transformer): this - Add a getter extractor, with a value transformer
     *   function getter(?string $propertyName, ?callable $transformer, ?callable $customAccessor): this - Add a getter extractor, with a value transformer and a custom accessor
     *
     * @param string|callable|null $propertyName The property name. If null use the child name.
     * @param callable|null $transformer The value transformer (transform model value to input value)
     * @param callable|null $customAccessor Custom getter function. Ff null use the symfony property accessor
     *
     * @return $this
     *
     * @see Getter
     */
    final public function getter($propertyName = null, ?callable $transformer = null, ?callable $customAccessor = null): self
    {
        return $this->extractor(new Getter($propertyName, $transformer, $customAccessor));
    }

    /**
     * Define hydrator using a setter
     *
     * Prototypes :
     *   function setter(): this - Add a setter hydrator, using the child name as property name
     *   function setter(string $propertyName): this - Add a setter hydrator, using $propertyName as property name
     *   function setter(callable $transformer): this - Add a setter hydrator, with a value transformer
     *   function setter(?string $propertyName, ?callable $transformer, ?callable $customAccessor): this - Add a setter hydrator, with a value transformer and a custom accessor
     *
     * @param string|callable|null $propertyName The property name. If null use the child name.
     * @param callable|null $transformer The value transformer (transform model value to input value)
     * @param callable|null $customAccessor Custom setter function. Ff null use the symfony property accessor
     *
     * @return $this
     *
     * @see Setter
     */
    final public function setter($propertyName = null, ?callable $transformer = null, ?callable $customAccessor = null): self
    {
        return $this->hydrator(new Setter($propertyName, $transformer, $customAccessor));
    }

    /**
     * Define the child creation factory or class name
     *
     * @param ChildCreationStrategyInterface|callable|string $factory The factory, or child class name
     *
     * @return $this
     */
    final public function childFactory($factory): self
    {
        $this->factory = $factory;

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
     * Note: The child element must a an aggregation element, like an embedded form, or an array element
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
     * Forward calls to the element builder
     *
     * @param string $name
     * @param array $arguments
     *
     * @return $this|mixed
     */
    final public function __call($name, $arguments)
    {
        $return = $this->elementBuilder->$name(...$arguments);

        return $return === $this->elementBuilder ? $this : $return;
    }
}
