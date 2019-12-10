<?php

namespace Bdf\Form\Aggregate;

use ArrayIterator;
use BadMethodCallException;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Leaf\LeafRootElement;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\ContainerTrait;
use Bdf\Form\Validator\NullValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Countable;
use Exception;

/**
 * Class ArrayElement
 */
final class ArrayElement implements ChildAggregateInterface, Countable
{
    use ContainerTrait;

    /**
     * @var ElementInterface
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
     * @param ElementInterface $templateElement
     * @param TransformerInterface|null $transformer
     * @param ValueValidatorInterface|null $validator
     */
    public function __construct(ElementInterface $templateElement, ?TransformerInterface $transformer = null, ?ValueValidatorInterface $validator = null)
    {
        $this->templateElement = $templateElement;
        $this->transformer = $transformer ?: NullTransformer::instance();
        $this->validator = $validator ?: NullValueValidator::instance();
        $this->error = FormError::null();
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
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Use import() or submit() for set an offset value');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Use import() or submit() for set an offset value');
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
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
    public function submit($data): ElementInterface
    {
        $this->valid = true;
        $this->children = [];

        try {
            $data = (array) $this->transformer->transformFromHttp($data, $this);
        } catch (Exception $e) {
            $this->valid = false;
            $this->error = FormError::message($e->getMessage());

            return $this;
        }

        $errors = [];

        foreach ((array) $data as $key => $value) {
            // @todo optimize the child implementation ?
            $child = new Child($key, $this->templateElement);
            $child->setParent($this);

            if (!$child->element()->submit($value)->valid()) {
                $this->valid = false;
                $errors[$key] = $child->error();
                $this->children[$key] = $child;

                continue;
            }

            // Remove null elements
            if ($child->element()->value() !== null) {
                $this->children[$key] = $child;
            }
        }

        if (!$this->valid) {
            $this->error = FormError::aggregate($errors);

            return $this;
        }

        $this->error = $this->validator->validate($this->value(), $this);
        $this->valid = $this->error->empty();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($entity): ElementInterface
    {
        $this->children = [];

        // @todo optimise ? Do not recreate children
        foreach ((array) $entity as $key => $value) {
            $child = new Child($key, $this->templateElement);
            $child->setParent($this);
            $child->element()->import($value);

            $this->children[$key] = $child;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value()
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
    public function error(): FormError
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function root(): RootElementInterface
    {
        if ($this->container) {
            return $this->container->parent()->root();
        }

        // @todo Use root form ?
        return new LeafRootElement($this);
    }

    /**
     * {@inheritdoc}
     */
    public function view()
    {
        // TODO: Implement view() method.
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
}
