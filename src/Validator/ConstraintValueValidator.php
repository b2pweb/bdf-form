<?php

namespace Bdf\Form\Validator;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Validator\Constraints\Chain;
use Symfony\Component\Validator\Constraint;

/**
 * Value validator using symfony constraint
 * The element will be used as "root" context object on the symfony validator
 */
final class ConstraintValueValidator implements ValueValidatorInterface
{
    /**
     * @var Constraint
     */
    private $constraint;


    /**
     * ConstraintValueValidator constructor.
     *
     * @param Constraint $constraint
     */
    public function __construct(Constraint $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, ElementInterface $element): FormError
    {
        $root = $element->root();

        $errors = $root->getValidator()
            ->startContext($element)
            ->validate($value, $this->constraint, $root->constraintGroups())
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
        return $this->constraint instanceof Chain
            ? $this->constraint->constraints
            : [$this->constraint]
        ;
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
        switch (count($constraints)) {
            case 0:
                return NullValueValidator::instance();

            case 1:
                return new self($constraints[0]);

            default:
                return new self(new Chain(['constraints' => $constraints]));
        }
    }
}
