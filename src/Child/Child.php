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
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\HttpValue;
use Bdf\Form\View\ElementViewInterface;
use WeakReference;

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
     * @var WeakReference<ChildAggregateInterface>
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
     * @var TransformerInterface
     */
    private $transformer;


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
    public function __construct(string $name, ElementInterface $element, ?HttpFieldsInterface $fields = null, array $filters = [], $defaultValue = null, ?HydratorInterface $hydrator = null, ?ExtractorInterface $extractor = null, array $dependencies = [], ?TransformerInterface $transformer = null)
    {
        $this->name = $name;
        $this->element = $element->setContainer($this);
        $this->fields = $fields ?: new ArrayOffsetHttpFields($name);
        $this->defaultValue = $defaultValue;
        $this->filters = $filters;
        $this->hydrator = $hydrator;
        $this->extractor = $extractor;
        $this->dependencies = $dependencies;
        $this->transformer = $transformer ?? NullTransformer::instance();
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
     *
     * @psalm-suppress NullableReturnStatement
     * @psalm-suppress InvalidNullableReturnType
     */
    public function parent(): ChildAggregateInterface
    {
        return $this->parent->get();
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(ChildAggregateInterface $parent): ChildInterface
    {
        if ($this->parent === null) {
            $this->parent = WeakReference::create($parent);
            return $this;
        }

        $child = clone $this;
        $child->parent = WeakReference::create($parent);

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

        /** @psalm-suppress PossiblyNullReference */
        $propertyAccessor = $this->parent->get()->root()->getPropertyAccessor();

        $this->extractor->setPropertyAccessor($propertyAccessor);
        $this->extractor->setFormElement($this);

        $value = $this->extractor->extract($entity);
        $value = $this->transformer->transformToHttp($value, $this->element);
        $this->extractor->setFormElement(null);

        $this->element->import($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fill(&$entity): void
    {
        if (!$this->hydrator) {
            return;
        }

        /** @psalm-suppress PossiblyNullReference */
        $propertyAccessor = $this->parent->get()->root()->getPropertyAccessor();

        $this->hydrator->setPropertyAccessor($propertyAccessor);
        $this->hydrator->setFormElement($this);

        $value = $this->element->value();
        $value = $this->transformer->transformFromHttp($value, $this->element);

        $this->hydrator->hydrate($entity, $value);
        $this->hydrator->setFormElement(null);
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
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $this->element->error($this->fields->get($field));
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
        $value = $this->fields->extract($httpValue);
        $default = $this->defaultValue;

        foreach ($this->filters as $filter) {
            $value = $filter->filter($value, $this, $default);
        }

        return HttpValue::orDefault($value, $default);
    }
}
