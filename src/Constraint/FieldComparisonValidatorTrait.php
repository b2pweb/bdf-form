<?php

namespace Bdf\Form\Constraint;

use Bdf\Form\ElementInterface;
use Bdf\Form\Util\FieldPath;
use Symfony\Component\Validator\Constraint;

/**
 * Handle FieldComparisonTrait constraints
 * The class must extends a subclass of AbstractComparisonValidator
 */
trait FieldComparisonValidatorTrait
{
    /**
     * {@inheritdoc}
     *
     * @psalm-suppress UndefinedMagicPropertyFetch
     * @psalm-suppress UndefinedMagicPropertyAssignment
     *
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var FieldComparisonTrait $constraint */
        /** @var ElementInterface $element */
        $element = $this->context->getRoot();
        $field = $constraint->field;

        if (!$field instanceof FieldPath) {
            $field = FieldPath::parse($field);
        }

        $constraint->value = $field->value($element);

        parent::validate($value, $constraint);
    }
}
