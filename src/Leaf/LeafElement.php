<?php

namespace Bdf\Form\Leaf;

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
 * Form element containing a single value
 */
abstract class LeafElement implements ElementInterface
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
     * @var mixed
     */
    private $value = null;

    /**
     * @var FormError
     */
    private $error;

    /**
     * @var bool
     */
    private $submitted = false;


    /**
     * LeafElement constructor.
     *
     * @param ValueValidatorInterface|null $validator
     * @param TransformerInterface|null $transformer
     */
    public function __construct(?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null)
    {
        $this->validator = $validator ?: NullValueValidator::instance();
        $this->transformer = $transformer ?: NullTransformer::instance();
        $this->error = FormError::null();
    }

    /**
     * {@inheritdoc}
     */
    final public function submit($data): ElementInterface
    {
        try {
            $this->submitted = true;
            $this->value = $this->toPhp($this->transformer->transformFromHttp($this->sanitize($data), $this));
            $this->error = $this->validator->validate($this->value, $this);
        } catch (Exception $e) {
            $this->error = FormError::message($e->getMessage());
            $this->value = $data; // @todo null ? keep original ?
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function valid(): bool
    {
        return $this->submitted && $this->error->empty();
    }

    /**
     * {@inheritdoc}
     */
    final public function error(): FormError
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    final public function import($entity): ElementInterface
    {
        $this->value = $entity;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function value()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    final public function httpValue()
    {
        return $this->transformer->transformToHttp($this->toHttp($this->value), $this);
    }

    /**
     * {@inheritdoc}
     */
    final public function root(): RootElementInterface
    {
        // @todo save the root ?
        if (!$this->container) {
            return new LeafRootElement($this);
        }

        return $this->container->parent()->root();
    }

    /**
     * {@inheritdoc}
     */
    public function view()
    {
        // TODO: Implement view() method.
    }

    /**
     * Cast the HTTP value to the PHP value
     *
     * @param mixed $httpValue
     *
     * @return mixed
     */
    abstract protected function toPhp($httpValue);

    /**
     * Transform the PHP value to the HTTP representation
     *
     * @param mixed $phpValue
     *
     * @return mixed
     */
    abstract protected function toHttp($phpValue);

    /**
     * Sanitize the raw HTTP value
     *
     * @param mixed $rawValue The raw HTTP value
     *
     * @return string|null
     */
    protected function sanitize($rawValue)
    {
        if (is_scalar($rawValue)) {
            return (string) $rawValue;
        }

        // Leaf element supports only scalar values
        return null;
    }
}
