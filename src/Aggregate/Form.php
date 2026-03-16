<?php

namespace Bdf\Form\Aggregate;

use BadMethodCallException;
use Bdf\Form\Aggregate\Collection\ChildrenCollectionInterface;
use Bdf\Form\Aggregate\Value\ValueGenerator;
use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Aggregate\View\FormView;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\ContainerTrait;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Exception;
use Iterator;
use Override;

use function assert;

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
 *
 * @template T as array|object
 * @implements FormInterface<T>
 */
final class Form implements FormInterface
{
    use ContainerTrait;

    /**
     * @var ValueValidatorInterface<T>
     */
    private readonly ValueValidatorInterface $validator;

    /**
     * Transformer to view value
     *
     * @var TransformerInterface
     */
    private readonly TransformerInterface $transformer;

    /**
     * @var ChildrenCollectionInterface
     */
    private readonly ChildrenCollectionInterface $children;

    /**
     * @var ValueGeneratorInterface<T>
     */
    private readonly ValueGeneratorInterface $generator;

    /**
     * Does the form is optional ?
     * An optional form will not be validated if the form is empty, and its value will be null
     *
     * @var bool
     */
    private readonly bool $optional;
    private ?RootElementInterface $root = null;
    private FormError $error;
    private bool $valid = false;

    /**
     * The generated value
     * This value is reset on submit to force regeneration
     *
     * @var T|null
     */
    private array|object|null $value = null;

    /**
     * Does the form has been submitted ?
     * In case of optional form, the form is not submitted if the value is empty
     *
     * @var bool
     */
    private bool $submitted = false;

    /**
     * Form constructor.
     *
     * @param ChildrenCollectionInterface $children
     * @param ValueValidatorInterface<T>|null $validator
     * @param TransformerInterface|null $transformer
     * @param ValueGeneratorInterface<T>|null $generator
     * @param bool $optional
     */
    public function __construct(ChildrenCollectionInterface $children, ?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null, ?ValueGeneratorInterface $generator = null, bool $optional = false)
    {
        $this->children = $children->duplicate($this);
        $this->validator = $validator ?? ConstraintValueValidator::empty();
        $this->transformer = $transformer ?? NullTransformer::instance();
        $this->error = FormError::null();
        /** @var ValueGeneratorInterface<T> */
        $this->generator = $generator ?? new ValueGenerator();
        $this->optional = $optional;
    }

    #[Override]
    public function submit(mixed $data): static
    {
        $this->valid = true;
        $this->value = null;

        if ($this->handleOptional($data)) {
            return $this;
        }

        $data = $this->transformHttpValue($data);

        $this->submitToChildrenAndValidate($data, 'submit');
        $this->submitted = true;

        return $this;
    }

    #[Override]
    public function patch(mixed $data): static
    {
        $this->valid = true;
        $this->value = null;

        if ($this->handleOptional($data)) {
            return $this;
        }

        if ($data !== null) {
            $data = $this->transformHttpValue($data);
        }

        $this->submitToChildrenAndValidate($data, 'patch');
        $this->submitted = true;

        return $this;
    }

    #[Override]
    public function valid(): bool
    {
        return $this->valid;
    }

    #[Override]
    public function failed(): bool
    {
        return !$this->valid;
    }

    #[Override]
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $field ? $this->error->withField($field) : $this->error;
    }

    #[Override]
    public function import(mixed $entity): static
    {
        if ($entity !== null && $entity !== []) {
            $this->generator->attach($entity);
        }

        $this->value = $entity;

        foreach ($this->children->reverseIterator() as $child) {
            $child->import($entity);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function value(): array|object|null
    {
        if ($this->value !== null) {
            return $this->value;
        }

        if (!$this->submitted && $this->optional) {
            return null;
        }

        $value = $this->generator->generate($this);

        foreach ($this->children->reverseIterator() as $child) {
            $child->fill($value);
        }

        /** @var T $value */
        return $this->value = $value;
    }

    #[Override]
    public function httpValue(): mixed
    {
        $http = [];

        foreach ($this->children as $child) {
            $http += $child->httpFields();
        }

        return $this->transformer->transformToHttp($http, $this);
    }

    #[Override]
    public function root(): RootElementInterface
    {
        if ($container = $this->container()) {
            return $container->parent()->root();
        }

        if ($this->root) {
            return $this->root;
        }

        return $this->root = new RootForm($this);
    }

    #[Override]
    public function view(?HttpFieldPath $field = null): FormView
    {
        $elements = [];

        foreach ($this->children as $child) {
            $elements[$child->name()] = $child->view($field);
        }

        return new FormView(self::class, $this->error->global(), $elements);
    }

    #[Override]
    public function getIterator(): Iterator
    {
        return $this->children->forwardIterator();
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->children[$offset]);
    }

    #[Override]
    public function offsetGet(mixed $offset): ChildInterface
    {
        /** @var ChildInterface */
        return $this->children[$offset];
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException(__CLASS__.' is immutable');
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException(__CLASS__.' is immutable');
    }

    public function __clone()
    {
        $this->children = $this->children->duplicate($this);
    }

    #[Override]
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
    private function transformHttpValue(mixed $data): mixed
    {
        try {
            $data = $this->transformer->transformFromHttp($data, $this);
        } catch (Exception $e) {
            $this->error = $this->validator->onTransformerException($e, $data, $this);
            $this->valid = $this->error->empty();

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
    private function submitToChildrenAndValidate(mixed $data, string $method): void
    {
        if (!$this->submitToChildren($data, $method)) {
            return;
        }

        // Do not generate value if it's not necessary
        $this->error = $this->validator->hasConstraints()
            ? $this->validator->validate($this->value(), $this)
            : FormError::null()
        ;

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
    private function submitToChildren(mixed $data, string $method): bool
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

    /**
     * Handle the optional case
     * If the form is optional and the data is empty, the form will be considered as not submitted and validation will be ignored
     *
     * @param mixed $data Submitted HTTP data
     *
     * @return bool true if the form is optional and not submitted, false otherwise
     */
    private function handleOptional(mixed $data): bool
    {
        if (!$this->optional || $data !== null && $data !== [] && $data !== '') {
            return false;
        }

        $this->submitted = false;
        $this->error = FormError::null();

        // Reset children value
        foreach ($this->children as $child) {
            $child->element()->import(null);
        }

        return true;
    }
}
