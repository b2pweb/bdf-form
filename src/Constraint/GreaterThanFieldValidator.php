<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\GreaterThanValidator;

/**
 * Validator for @see GreaterThanField
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GreaterThanFieldValidator extends GreaterThanValidator
{
    use FieldComparisonValidatorTrait;
}
