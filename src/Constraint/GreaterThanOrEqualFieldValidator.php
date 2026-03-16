<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqualValidator;

/**
 * Validator for @see GreaterThanOrEqualField
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GreaterThanOrEqualFieldValidator extends GreaterThanOrEqualValidator
{
    use FieldComparisonValidatorTrait;
}
