<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Choice\Choiceable;
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Choice\ChoiceView;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\ContainerTrait;
use Bdf\Form\Validator\NullValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Bdf\Form\View\ConstraintsNormalizer;
use Bdf\Form\View\ElementViewInterface;
use Bdf\Form\View\FieldViewInterface;
use Exception;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form element containing a single value
 */
abstract class LeafElement implements ElementInterface, Choiceable
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
     * @var ChoiceInterface|null
     */
    private $choices;

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
     * @param ChoiceInterface|null $choices
     */
    public function __construct(?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null, ?ChoiceInterface $choices = null)
    {
        $this->validator = $validator ?: NullValueValidator::instance();
        $this->transformer = $transformer ?: NullTransformer::instance();
        $this->error = FormError::null();
        $this->choices = $choices;
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
            $this->error = FormError::message($e->getMessage(), 'TRANSFORM_ERROR');
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
     *
     * @return FieldViewInterface
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        $normalizedConstraints = ConstraintsNormalizer::normalize($this->validator);

        return new SimpleElementView(
            static::class,
            (string) $field,
            $this->httpValue(),
            $this->error->global(),
            isset($normalizedConstraints[NotBlank::class]),
            $normalizedConstraints,
            $this->choiceView()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function choices(): ?ChoiceInterface
    {
        return $this->choices;
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

    /**
     * Get the choice view and apply value transformation and selected value
     *
     * @return array|null
     * @see ChoiceInterface::view()
     */
    protected function choiceView(): ?array
    {
        if ($this->choices === null) {
            return null;
        }

        return $this->choices->view(function (ChoiceView $view) {
            $view->setSelected($view->value() == $this->value());
            $view->setValue($this->transformer->transformToHttp($this->toHttp($view->value()), $this));
        });
    }
}
