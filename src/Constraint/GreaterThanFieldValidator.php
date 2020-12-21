<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\GreaterThanValidator;

/**
 * Validator for @see GreaterThanField
 */
class GreaterThanFieldValidator extends GreaterThanValidator
{
    use FieldComparisonValidatorTrait;
}
