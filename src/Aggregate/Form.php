<?php

namespace Bdf\Form\Aggregate;

use BadMethodCallException;
use Bdf\Form\Aggregate\Collection\ChildrenCollectionInterface;
use Bdf\Form\Aggregate\Value\ValueGenerator;
use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\ContainerTrait;
use Bdf\Form\Validator\NullValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Exception;

/**
 * The base form element
 */
final class Form implements FormInterface
{
    use ContainerTrait;

    /**
     * @var ValueValidatorInterface
     */
    private $validator;

    /**
     * Transformer to view value
     *
     * @var TransformerInterface
     */
    private $transformer;

    /**
     * @var ChildrenCollectionInterface
     */
    private $children;

    /**
     * @var ValueGeneratorInterface
     */
    private $generator;

    /**
     * @var RootElementInterface|null
     */
    private $root;

    /**
     * @var FormError
     */
    private $error;

    /**
     * @var bool
     */
    private $valid = false;


    /**
     * Form constructor.
     *
     * @param ChildrenCollectionInterface $children
     * @param ValueValidatorInterface|null $validator
     * @param TransformerInterface|null $transformer
     * @param ValueGeneratorInterface|null $generator
     */
    public function __construct(ChildrenCollectionInterface $children, ?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null, ?ValueGeneratorInterface $generator = null)
    {
        $this->children = $children->duplicate($this);
        $this->validator = $validator ?: NullValueValidator::instance();
        $this->transformer = $transformer ?: NullTransformer::instance();
        $this->error = FormError::null();
        $this->generator = $generator ?: new ValueGenerator();
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): ElementInterface
    {
        $this->valid = true;
        $data = $this->transformHttpValue($data);

        if (!$this->submitToChildren($data)) {
            return $this;
        }

        $this->error = $this->validator->validate($this->value(), $this);
        $this->valid = $this->error->empty();

        return $this;
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
    public function import($entity): ElementInterface
    {
        $this->generator->attach($entity);

        foreach ($this->children as $child) {
            $child->import($entity);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value()
    {
        $value = $this->generator->generate($this);

        foreach ($this->children as $child) {
            $child->fill($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function httpValue()
    {
        $http = [];

        foreach ($this->children as $child) {
            $http += $child->httpFields();
        }

        return $this->transformer->transformToHttp($http, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function root(): RootElementInterface
    {
        if ($this->container) {
            return $this->container->parent()->root();
        }

        if ($this->root) {
            return $this->root;
        }

        return $this->root = new RootForm($this);
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
    public function getIterator()
    {
        return $this->children->forwardIterator();
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
    public function offsetGet($offset): ChildInterface
    {
        return $this->children[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException(__CLASS__.' is immutable');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException(__CLASS__.' is immutable');
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        $this->children = $this->children->duplicate($this);
    }

    /**
     * {@inheritdoc}
     */
    public function attach($entity): FormInterface
    {
        $this->generator->attach($entity);

        return $this;
    }

    /**
     * Set the root element
     *
     * @param RootElementInterface $root
     *
     * @internal used by the builder
     */
    public function setRoot(RootElementInterface $root): void
    {
        $this->root = $root;
    }

    /**
     * Transform the submitted value
     * If the transformation fail the error will be set
     *
     * @param mixed $data
     *
     * @return mixed The transformed value
     */
    private function transformHttpValue($data)
    {
        try {
            $data = $this->transformer->transformFromHttp($data, $this);
        } catch (Exception $e) {
            $this->error = FormError::message($e->getMessage());
            $this->valid = false;

            // Reset children values
            foreach ($this->children->all() as $child) {
                $child->element()->import(null);
            }

            return null;
        }

        return $data;
    }

    /**
     * Submit the transformed http data to children
     *
     * @param mixed $data Data to submit
     *
     * @return bool false on fail, or true on success
     */
    private function submitToChildren($data): bool
    {
        if (!$this->valid) {
            return false;
        }

        $errors = [];

        foreach ($this->children->reverseIterator() as $child) {
            if (!$child->submit($data)) {
                $this->valid = false;
                $errors[$child->name()] = $child->error();
            }
        }

        if (!$this->valid) {
            $this->error = FormError::aggregate($errors);

            return false;
        }

        return true;
    }
}
