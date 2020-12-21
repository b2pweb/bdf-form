<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

/**
 * The current field value must be >= to the other field
 */
class GreaterThanOrEqualField extends GreaterThanOrEqual
{
    use FieldComparisonTrait;
}
