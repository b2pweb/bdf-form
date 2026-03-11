<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\LessThanOrEqualValidator;

/**
 * Validator for @see LessThanOrEqualField
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LessThanOrEqualFieldValidator extends LessThanOrEqualValidator
{
    use FieldComparisonValidatorTrait;
}
