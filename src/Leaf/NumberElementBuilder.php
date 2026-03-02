<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use ReflectionClass;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Positive;

use function is_array;
use function sprintf;
use function trigger_error;

/**
 * Base builder for an number element
 *
 * @template E as \Bdf\Form\ElementInterface
 * @extends AbstractElementBuilder<E>
 */
abstract class NumberElementBuilder extends AbstractElementBuilder
{
    use ChoiceBuilderTrait;

    /**
     * @var bool
     */
    private $raw = false;


    /**
     * NumberElementBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(?RegistryInterface $registry = null)
    {
        parent::__construct($registry);

        $this->addTransformerProvider([$this, 'provideNumberTransformer']);
    }

    /**
     * Set the minimal value constraint
     *
     * @param int|float $min The minimal value (included)
     * @param string|null $message The error message
     *
     * @return $this
     */
    public function min($min, ?string $message = null): self
    {
        static $isSf4 = null;

        if ($isSf4 === null) {
            /** @psalm-suppress PossiblyNullReference */
            $isSf4 = (new ReflectionClass(GreaterThanOrEqual::class))->getConstructor()->getNumberOfParameters() === 1;
        }

        $this->satisfy($isSf4
            ? new GreaterThanOrEqual(['value' => $min, 'message' => $message])
            : new GreaterThanOrEqual($min, null, $message)
        );

        return $this;
    }

    /**
     * Set the maximal value constraint
     *
     * @param int|float $max The maximal value (included)
     * @param string|null $message The error message
     *
     * @return $this
     */
    public function max($max, ?string $message = null): self
    {
        static $isSf4 = null;

        if ($isSf4 === null) {
            /** @psalm-suppress PossiblyNullReference */
            $isSf4 = (new ReflectionClass(LessThanOrEqual::class))->getConstructor()->getNumberOfParameters() === 1;
        }

        $this->satisfy($isSf4
            ? new LessThanOrEqual(['value' => $max, 'message' => $message])
            : new LessThanOrEqual($max, null, $message)
        );

        return $this;
    }

    /**
     * The number must be positive
     *
     * @param array|string|null $message The error message
     *
     * @return $this
     * @see Positive
     */
    public function positive($message = null): self
    {
        static $isSf4 = null;

        if ($isSf4 === null) {
            /** @psalm-suppress PossiblyNullReference */
            $isSf4 = (new ReflectionClass(Positive::class))->getConstructor()->getNumberOfParameters() === 1;
        }

        if (is_array($message)) {
            @trigger_error(sprintf('Passing an array of options on %s is deprecated since 1.7 and will be removed on 2.0, use named arguments instead.', __METHOD__), E_USER_DEPRECATED);
            $message = $message['message'] ?? null;
        }

        return $this->satisfy($isSf4
            ? new Positive(['message' => $message])
            : new Positive(null, $message)
        );
    }

    /**
     * Enable raw number mode
     *
     * In raw mode, the value will not be parsed according the the locale, but only cast the value to the php number type
     * This mode is useful when the input format is normalized, like in APIs
     *
     * @param bool $flag Enable or disable the raw mode
     *
     * @return $this
     */
    public function raw(bool $flag = true): self
    {
        $this->raw = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultTransformerExceptionConstraintOptions(): array
    {
        return [
            'message' => 'The value is not a valid number.',
            'code' => 'INVALID_NUMBER_ERROR',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultTransformerExceptionConstraint(): TransformerExceptionConstraint
    {
        return new TransformerExceptionConstraint(
            null,
            /*message:*/ 'The value is not a valid number.',
            /*code:*/ 'INVALID_NUMBER_ERROR',
        );
    }

    /**
     * Creates the localized number transformer
     *
     * @return TransformerInterface
     */
    abstract protected function numberTransformer(): TransformerInterface;

    final protected function provideNumberTransformer(RegistryInterface $registry): array
    {
        if (!$this->raw) {
            return [$this->numberTransformer()];
        }

        return [];
    }
}
