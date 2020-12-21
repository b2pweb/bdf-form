<?php

namespace Bdf\Form\Constraint;

use Bdf\Form\ElementInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Handle FieldComparisonTrait constraints
 * The class must extends a subclass of AbstractComparisonValidator
 */
trait FieldComparisonValidatorTrait
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ElementInterface $element */
        $element = $this->context->getRoot();

        if (isset($element->container()->parent()[$constraint->field])) {
            $constraint->value = $element->container()->parent()[$constraint->field]->element()->value(); // @todo handle path
        }

        parent::validate($value, $constraint);
    }
}
