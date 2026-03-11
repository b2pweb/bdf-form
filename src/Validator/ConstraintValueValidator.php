<?php

namespace Bdf\Form\Validator;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Exception;
use Override;
use Symfony\Component\Validator\Constraint;

/**
 * Value validator using symfony constraint
 * The element will be used as "root" context object on the symfony validator
 *
 * @template T
 * @implements ValueValidatorInterface<T>
 */
final readonly class ConstraintValueValidator implements ValueValidatorInterface
{
    /**
     * @var Constraint[]
     */
    private array $constraints;

    /**
     * @var TransformerExceptionConstraint
     */
    private TransformerExceptionConstraint $transformerExceptionConstraint;


    /**
     * ConstraintValueValidator constructor.
     *
     * @param Constraint[] $constraints
     * @param TransformerExceptionConstraint|null $transformerExceptionConstraint
     */
    public function __construct(array $constraints = [], ?TransformerExceptionConstraint $transformerExceptionConstraint = null)
    {
        $this->constraints = $constraints;
        $this->transformerExceptionConstraint = $transformerExceptionConstraint ?? new TransformerExceptionConstraint();
    }

    #[Override]
    public function validate($value, ElementInterface $element): FormError
    {
        if (!$this->constraints) {
            return FormError::null();
        }

        $root = $element->root();
        $groups = $root->constraintGroups();

        /** @psalm-suppress TooManyArguments */
        // Note: Wrapping the element into a WeakReference will cause a BC break
        $context = $root->getValidator()->startContext(\WeakReference::create($element));

        foreach ($this->constraints as $constraint) {
            $errors = $context->validate($value, $constraint, $groups)->getViolations();

            if ($errors->has(0)) {
                return FormError::violation($errors->get(0));
            }
        }

        return FormError::null();
    }

    #[Override]
    public function onTransformerException(Exception $exception, $value, ElementInterface $element): FormError
    {
        if ($this->transformerExceptionConstraint->ignoreException) {
            return FormError::null();
        }

        /** @psalm-suppress TooManyArguments */
        $errors = $element->root()
            ->getValidator()
            ->startContext($element)
            ->validate($value, $this->transformerExceptionConstraint->withException($exception))
            ->getViolations()
        ;

        if ($errors->has(0)) {
            return FormError::violation($errors->get(0));
        }

        return FormError::null();
    }

    #[Override]
    public function constraints(): array
    {
        return $this->constraints;
    }

    #[Override]
    public function hasConstraints(): bool
    {
        return $this->constraints !== [];
    }

    /**
     * Get the empty value validator instance
     *
     * @return ConstraintValueValidator<mixed>
     */
    public static function empty(): self
    {
        static $instance = new self();

        return $instance;
    }
}
