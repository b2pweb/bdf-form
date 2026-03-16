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
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Bdf\Form\View\ConstraintsNormalizer;
use Bdf\Form\View\FieldViewInterface;
use Exception;
use Override;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form element containing a single value
 *
 * @template T
 *
 * @implements ElementInterface<T>
 * @implements Choiceable<T>
 */
abstract class LeafElement implements ElementInterface, Choiceable
{
    use ContainerTrait;

    /**
     * @var ValueValidatorInterface<T>
     */
    private readonly ValueValidatorInterface $validator;

    /**
     * Transformer to view value
     */
    private readonly TransformerInterface $transformer;

    /**
     * @var ChoiceInterface<T>|null
     */
    private ?ChoiceInterface $choices;

    /**
     * @var T|null
     */
    private mixed $value = null;
    private FormError $error;
    private bool $submitted = false;


    /**
     * LeafElement constructor.
     *
     * @param ValueValidatorInterface<T>|null $validator
     * @param TransformerInterface|null $transformer
     * @param ChoiceInterface<T>|null $choices
     */
    public function __construct(?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null, ?ChoiceInterface $choices = null)
    {
        $this->validator = $validator ?: ConstraintValueValidator::empty();
        $this->transformer = $transformer ?: NullTransformer::instance();
        $this->error = FormError::null();
        $this->choices = $choices;
    }

    #[Override]
    final public function submit(mixed $data): static
    {
        $shouldBeValidated = true;

        try {
            $this->submitted = true;
            $this->value = $this->toPhp($this->transformer->transformFromHttp($this->sanitize($data), $this));
        } catch (Exception $e) {
            $this->error = $this->validator->onTransformerException($e, $data, $this);
            $this->value = $data; // @todo null ? keep original ?
            $shouldBeValidated = $this->error->empty();
        }

        // Only validate on successfully transformation or if the transformation error is ignored
        if ($shouldBeValidated) {
            $this->error = $this->validator->validate($this->value, $this);
        }

        return $this;
    }

    #[Override]
    final public function patch(mixed $data): static
    {
        // A data is provided : simply submit the data
        if ($data !== null) {
            return $this->submit($data);
        }

        $this->submitted = true;
        // Revalidate the element
        $this->error = $this->validator->validate($this->value, $this);

        return $this;
    }

    #[Override]
    final public function valid(): bool
    {
        return $this->submitted && $this->error->empty();
    }

    #[Override]
    final public function failed(): bool
    {
        return !$this->valid();
    }

    #[Override]
    final public function error(?HttpFieldPath $field = null): FormError
    {
        return $field ? $this->error->withField($field) : $this->error;
    }

    #[Override]
    final public function import(mixed $entity): static
    {
        $this->value = $this->tryCast($entity);

        return $this;
    }

    #[Override]
    final public function value(): mixed
    {
        return $this->value;
    }

    #[Override]
    final public function httpValue(): mixed
    {
        try {
            return $this->transformer->transformToHttp($this->toHttp($this->value), $this);
        } catch (Exception $e) {
            return $this->value;
        }
    }

    #[Override]
    final public function root(): RootElementInterface
    {
        if ($container = $this->container()) {
            return $container->parent()->root();
        }

        // @todo save the root ?
        return new LeafRootElement($this);
    }

    #[Override]
    public function view(?HttpFieldPath $field = null): FieldViewInterface
    {
        [$required, $normalizedConstraints] = $this->parseConstraints($this->validator);

        return new SimpleElementView(
            static::class,
            (string) $field,
            $this->httpValue(),
            $this->error->global(),
            $required,
            $normalizedConstraints,
            $this->choiceView()
        );
    }

    #[Override]
    final public function choices(): ?ChoiceInterface
    {
        return $this->choices;
    }

    /**
     * Cast the HTTP value to the PHP value
     *
     * @param mixed $httpValue
     *
     * @return T|null
     */
    abstract protected function toPhp(mixed $httpValue)/*: mixed*/;

    /**
     * Transform the PHP value to the HTTP representation
     *
     * @param T|null $phpValue
     *
     * @return mixed
     */
    abstract protected function toHttp(mixed $phpValue)/*: mixed*/;

    /**
     * Try to convert the value into the element type
     *
     * @param mixed $value Value to cast
     * @psalm-assert T|null $value
     *
     * @return T|null
     *
     * @throws \TypeError If the value type is not supported by the element
     *
     * @see LeafElement::import()
     */
    protected function tryCast(mixed $value)/*: mixed*/
    {
        return $value;
    }

    /**
     * Sanitize the raw HTTP value
     *
     * @param mixed $rawValue The raw HTTP value
     * @return mixed
     */
    protected function sanitize(mixed $rawValue)/*: mixed*/
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
        return $this->choices?->view(function (ChoiceView $view) {
            $view->setSelected($view->value() == $this->value());
            $view->setValue($this->transformer->transformToHttp($this->toHttp($view->value()), $this));
        });
    }

    /**
     * Parse constraints and required value
     *
     * By default, will use {@see ConstraintsNormalizer} to extract constraints,
     * and check the presence of {@see NotBlank} constraint to determine if the field is required
     *
     * @return list{bool, array}
     */
    protected function parseConstraints(ValueValidatorInterface $validator): array
    {
        $normalizedConstraints = ConstraintsNormalizer::normalize($validator);

        return [
            isset($normalizedConstraints[NotBlank::class]),
            $normalizedConstraints
        ];
    }
}
