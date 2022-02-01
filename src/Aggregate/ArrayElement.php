<?php

namespace Bdf\Form\Aggregate;

use ArrayIterator;
use BadMethodCallException;
use Bdf\Form\Aggregate\View\ArrayElementView;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Choice\Choiceable;
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Choice\ChoiceView;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Leaf\LeafRootElement;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\ContainerTrait;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Bdf\Form\View\ConstraintsNormalizer;
use Bdf\Form\View\ElementViewInterface;
use Countable;
use Exception;
use Iterator;
use Symfony\Component\Validator\Constraints\NotBlank;
use TypeError;

/**
 * Dynamic collection of elements
 * This element may be a simple list array (i.e. with numeric offset), or associative array (with string offset)
 * Contrary to Form, all components elements are identically
 *
 * Array element can be used as leaf element (like with CSV string), or root of embedded forms
 *
 * @see ArrayElementBuilder For build the element
 *
 * @template T
 *
 * @implements ChildAggregateInterface<T[]>
 * @implements Choiceable<T>
 */
final class ArrayElement implements ChildAggregateInterface, Countable, Choiceable
{
    use ContainerTrait;

    /**
     * @var ElementInterface<T>
     */
    private $templateElement;

    /**
     * @var TransformerInterface
     */
    private $transformer;

    /**
     * @var ValueValidatorInterface
     */
    private $validator;

    /**
     * @var ChoiceInterface|null
     */
    private $choices;

    /**
     * @var bool
     */
    private $valid = false;

    /**
     * @var FormError
     */
    private $error;

    /**
     * @var ChildInterface[]
     */
    private $children = [];


    /**
     * ArrayElement constructor.
     *
     * @param ElementInterface<T> $templateElement Inner element
     * @param TransformerInterface|null $transformer
     * @param ValueValidatorInterface|null $validator
     * @param ChoiceInterface<T>|null $choices
     */
    public function __construct(ElementInterface $templateElement, ?TransformerInterface $transformer = null, ?ValueValidatorInterface $validator = null, ?ChoiceInterface $choices = null)
    {
        $this->templateElement = $templateElement;
        $this->transformer = $transformer ?: NullTransformer::instance();
        $this->validator = $validator ?: ConstraintValueValidator::empty();
        $this->error = FormError::null();
        $this->choices = $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): ChildInterface
    {
        return $this->children[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->children[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Use import() or submit() for set an offset value');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Use import() or submit() for set an offset value');
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function choices(): ?ChoiceInterface
    {
        return $this->choices;
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): ElementInterface
    {
        $this->valid = true;

        $lastChildren = $this->children;
        $this->children = [];

        try {
            $data = (array) $this->transformer->transformFromHttp($data, $this);
        } catch (Exception $e) {
            $this->error = $this->validator->onTransformerException($e, $data, $this);

            if (!$this->valid = $this->error->empty()) {
                return $this;
            }

            $data = [];
        }

        $errors = [];

        foreach ($data as $key => $value) {
            $child = $lastChildren[$key] ?? (new Child($key, $this->templateElement))->setParent($this);

            $child->element()->submit($value);

            // Remove null elements
            if ($child->element()->value() === null) {
                continue;
            }

            $this->children[$key] = $child;

            if (!$child->element()->valid()) {
                $this->valid = false;
                $errors[$key] = $child->error();

                continue;
            }
        }

        $this->validate($errors);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function patch($data): ElementInterface
    {
        $this->valid = true;

        if ($data !== null) {
            return $this->submit($data);
        }

        $errors = [];

        // Keep all elements, and propagate the patch
        foreach ($this->children as $key => $child) {
            if (!$child->element()->patch(null)->valid()) {
                $this->valid = false;
                $errors[$key] = $child->error();
                $this->children[$key] = $child;
            }
        }

        $this->validate($errors);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($entity): ElementInterface
    {
        if ($entity === null) {
            $entity = [];
        } elseif (!is_iterable($entity)) {
            throw new TypeError('The import()\'ed value of a '.static::class.' must be iterable or null');
        }

        $this->children = [];

        // @todo optimise ? Do not recreate children
        foreach ($entity as $key => $value) {
            $child = new Child($key, $this->templateElement);
            $child->setParent($this);
            $child->element()->import($value);

            $this->children[$key] = $child;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return T[]
     */
    public function value(): array
    {
        $value = [];

        foreach ($this->children as $child) {
            $value[$child->name()] = $child->element()->value();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function httpValue()
    {
        $value = [];

        foreach ($this->children as $child) {
            $value[$child->name()] = $child->element()->httpValue();
        }

        return $this->transformer->transformToHttp($value, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->valid;
    }

    /**
     * {@inheritdoc}
     */
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $field ? $this->error->withField($field) : $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function root(): RootElementInterface
    {
        if ($container = $this->container()) {
            return $container->parent()->root();
        }

        // @todo Use root form ?
        return new LeafRootElement($this);
    }

    /**
     * {@inheritdoc}
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        $elements = [];

        foreach ($this->children as $key => $child) {
            $elements[$key] = $child->view($field);
        }

        $constraints = ConstraintsNormalizer::normalize($this->validator);

        return new ArrayElementView(
            self::class,
            (string) $field,
            $this->httpValue(),
            $this->error->global(),
            $elements,
            isset($constraints[NotBlank::class]),
            $constraints,
            $this->choiceView()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        $children = $this->children;
        $this->children = [];

        foreach ($children as $name => $child) {
            $this->children[$name] = $child->setParent($this);
        }
    }

    /**
     * Get the choice view and apply value transformation and selected value
     *
     * @return array|null
     * @see ChoiceInterface::view()
     */
    private function choiceView(): ?array
    {
        if ($this->choices === null) {
            return null;
        }

        // Use inner element for transform choice values
        $innerElement = clone $this->templateElement;

        return $this->choices->view(function (ChoiceView $view) use($innerElement) {
            $view->setSelected(in_array($view->value(), $this->value()));
            $view->setValue($innerElement->import($view->value())->httpValue());
        });
    }

    /**
     * Validate the array value
     *
     * @param array $childrenErrors The children errors
     */
    private function validate(array $childrenErrors): void
    {
        if (!$this->valid) {
            $this->error = FormError::aggregate($childrenErrors);
            return;
        }

        $this->error = $this->validator->validate($this->value(), $this);
        $this->valid = $this->error->empty();
    }
}
