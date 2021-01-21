<?php

namespace Bdf\Form\Validator;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
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
     * @var Constraint[]
     */
    private $constraints;


    /**
     * ConstraintValueValidator constructor.
     *
     * @param Constraint[] $constraints
     */
    public function __construct(array $constraints)
    {
        $this->constraints = $constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, ElementInterface $element): FormError
    {
        $root = $element->root();

        /** @psalm-suppress TooManyArguments */
        $errors = $root->getValidator()
            ->startContext($element)
            ->validate($value, $this->constraints, $root->constraintGroups())
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
     * Create the value validator from list of symfony constraints
     *
     * @param Constraint[] $constraints
     *
     * @return ValueValidatorInterface
     */
    public static function fromConstraints(array $constraints = []): ValueValidatorInterface
    {
        return empty($constraints) ? NullValueValidator::instance() : new self($constraints);
    }
}
