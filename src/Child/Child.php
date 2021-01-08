<?php

namespace Bdf\Form\Child;

use Bdf\Form\Aggregate\ChildAggregateInterface;
use Bdf\Form\Child\Http\ArrayOffsetHttpFields;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Child\Http\HttpFieldsInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Filter\FilterInterface;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Bdf\Form\View\ElementViewInterface;

/**
 * Child which extract HTTP field value from a simple array access
 */
final class Child implements ChildInterface
{
    /**
     * @var ElementInterface
     */
    private $element;

    /**
     * @var ChildAggregateInterface
     */
    private $parent;

    /**
     * @var string
     */
    private $name;

    /**
     * @var HttpFieldsInterface
     */
    private $fields;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var FilterInterface[]
     */
    private $filters;

    /**
     * @var HydratorInterface|null
     */
    private $hydrator;

    /**
     * @var ExtractorInterface|null
     */
    private $extractor;

    /**
     * @var string[]
     */
    private $dependencies;


    /**
     * ArrayOffsetChild constructor.
     *
     * @param string $name
     * @param ElementInterface $element
     * @param HttpFieldsInterface|null $fields
     * @param FilterInterface[] $filters
     * @param mixed $defaultValue
     * @param HydratorInterface|null $hydrator
     * @param ExtractorInterface|null $extractor
     * @param string[] $dependencies
     */
    public function __construct(string $name, ElementInterface $element, ?HttpFieldsInterface $fields = null, array $filters = [], $defaultValue = null, ?HydratorInterface $hydrator = null, ?ExtractorInterface $extractor = null, array $dependencies = [])
    {
        $this->name = $name;
        $this->element = $element->setContainer($this);
        $this->fields = $fields ?: new ArrayOffsetHttpFields($name);
        $this->defaultValue = $defaultValue;
        $this->filters = $filters;
        $this->hydrator = $hydrator;
        $this->extractor = $extractor;
        $this->dependencies = $dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function element(): ElementInterface
    {
        return $this->element;
    }

    /**
     * {@inheritdoc}
     */
    public function parent(): ChildAggregateInterface
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(ChildAggregateInterface $parent): ChildInterface
    {
        if ($this->parent === null) {
            $this->parent = $parent;
            return $this;
        }

        $child = clone $this;
        $child->parent = $parent;

        return $child;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function import($entity): void
    {
        if (!$this->extractor) {
            return;
        }

        $propertyAccessor = $this->parent->root()->getPropertyAccessor();

        $this->extractor->setPropertyAccessor($propertyAccessor);
        $this->extractor->setFormElement($this);

        $this->extractor->extract($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function fill(&$entity): void
    {
        if (!$this->hydrator) {
            return;
        }

        $propertyAccessor = $this->parent->root()->getPropertyAccessor();

        $this->hydrator->setPropertyAccessor($propertyAccessor);
        $this->hydrator->setFormElement($this);

        $this->hydrator->hydrate($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): bool
    {
        $value = $this->extractValue($data);

        return $this->element->submit($value)->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function patch($data): bool
    {
        $value = $data !== null && $this->fields->contains($data)
            ? $this->extractValue($data)
            : null
        ;

        return $this->element->patch($value)->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function httpFields(): array
    {
        return $this->fields->format($this->element->httpValue());
    }

    /**
     * {@inheritdoc}
     */
    public function error(): FormError
    {
        return $this->element->error();
    }

    /**
     * {@inheritdoc}
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        return $this->element->view($this->fields->get($field));
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        $this->element = $this->element->setContainer($this);
    }

    /**
     * Extract HTTP value and apply filters
     *
     * @param mixed $httpValue
     * @return mixed The filtered value
     */
    private function extractValue($httpValue)
    {
        $value = $this->fields->extract($httpValue, $this->defaultValue);

        foreach ($this->filters as $filter) {
            $value = $filter->filter($value, $this);
        }

        return $value;
    }
}
