<?php

namespace Bdf\Form\Validator;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Exception;
use Symfony\Component\Validator\Constraint;

/**
 * Value validator using symfony constraint
 * The element will be used as "root" context object on the symfony validator
 *
 * @template T
 * @implements ValueValidatorInterface<T>
 */
final class ConstraintValueValidator implements ValueValidatorInterface
{
    /**
     * @var self
     */
    private static $emptyInstance;

    /**
     * @var Constraint[]
     */
    private $constraints;

    /**
     * @var TransformerExceptionConstraint
     */
    private $transformerExceptionConstraint;


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

    /**
     * {@inheritdoc}
     */
    public function validate($value, ElementInterface $element): FormError
    {
        if (!$this->constraints) {
            return FormError::null();
        }

        $root = $element->root();
        $groups = $root->constraintGroups();

        /** @psalm-suppress TooManyArguments */
        $context = $root->getValidator()->startContext($element);

        foreach ($this->constraints as $constraint) {
            $errors = $context->validate($value, $constraint, $groups)->getViolations();

            if ($errors->has(0)) {
                return FormError::violation($errors->get(0));
            }
        }

        return FormError::null();
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function hasConstraints(): bool
    {
        return !empty($this->constraints);
    }

    /**
     * Get the empty value validator instance
     *
     * @return ConstraintValueValidator<mixed>
     */
    public static function empty(): self
    {
        if (self::$emptyInstance) {
            return self::$emptyInstance;
        }

        return self::$emptyInstance = new self();
    }
}
