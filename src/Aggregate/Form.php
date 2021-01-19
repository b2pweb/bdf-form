<?php

namespace Bdf\Form\Aggregate;

use BadMethodCallException;
use Bdf\Form\Aggregate\Collection\ChildrenCollectionInterface;
use Bdf\Form\Aggregate\Value\ValueGenerator;
use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Aggregate\View\FormView;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\ContainerTrait;
use Bdf\Form\Validator\NullValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Bdf\Form\View\ElementViewInterface;
use Exception;

/**
 * The base form element
 * A form is an static aggregate of elements, unlike ArrayElement which is a dynamic aggregate
 *
 * The form will submit HTTP value to all it's children, and then perform it's validation process (if defined)
 * A form cannot have a "global" error if there is at least one child on error
 *
 * To access to children elements, use array access : `$form['child']->element()`
 * Note: The return value of array access is a ChildInterface. Use `ChildInterface::element()` to get the element
 *
 * <code>
 * // Show form view
 * $view = $form->import($entity)->view();
 *
 * echo $view['foo']->id('foo')->class('form-control');
 * echo $view['bar']->id('bar')->class('form-control');
 *
 * // Submit form
 * if (!$form->submit($request->post())->valid()) {
 *     throw new ApiException($form->error()->print(new ApiErrorPrinter()));
 * }
 *
 * $entity = $form->attach($entity)->value();
 * </code>
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
     * The generated value
     * This value is reset on submit to force regeneration
     *
     * @var mixed|null
     */
    private $value;

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
        $this->value = null;

        $data = $this->transformHttpValue($data);

        $this->submitToChildrenAndValidate($data, 'submit');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function patch($data): ElementInterface
    {
        $this->valid = true;
        $this->value = null;

        if ($data !== null) {
            $data = $this->transformHttpValue($data);
        }

        $this->submitToChildrenAndValidate($data, 'patch');

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
        $this->value = $entity;

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
        if ($this->value !== null) {
            return $this->value;
        }

        $this->value = $this->generator->generate($this);

        foreach ($this->children as $child) {
            $child->fill($this->value);
        }

        return $this->value;
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
     *
     * @return FormView
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        $elements = [];

        foreach ($this->children as $child) {
            $elements[$child->name()] = $child->view($field);
        }

        return new FormView(self::class, $this->error->global(), $elements);
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
        $this->value = null; // The value is only attached : it must be filled when calling value()

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
            $this->error = FormError::message($e->getMessage(), 'TRANSFORM_ERROR');
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
     * Submit the transformed http data to children and validate the value
     *
     * @param mixed $data Data to submit
     * @param string $method The submit method to call. Should be "submit" or "patch"
     */
    private function submitToChildrenAndValidate($data, string $method): void
    {
        if (!$this->submitToChildren($data, $method)) {
            return;
        }

        $this->error = $this->validator->validate($this->value(), $this);
        $this->valid = $this->error->empty();
    }

    /**
     * Submit the transformed http data to children
     *
     * @param mixed $data Data to submit
     * @param string $method The submit method to call. Should be "submit" or "patch"
     *
     * @return bool false on fail, or true on success
     */
    private function submitToChildren($data, string $method): bool
    {
        if (!$this->valid) {
            return false;
        }

        $errors = [];

        foreach ($this->children->reverseIterator() as $child) {
            if (!$child->$method($data)) {
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
