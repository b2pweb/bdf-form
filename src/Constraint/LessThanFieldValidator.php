<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\LessThanValidator;

/**
 * Validator for @see LessThanField
 */
class LessThanFieldValidator extends LessThanValidator
{
    use FieldComparisonValidatorTrait;
}
