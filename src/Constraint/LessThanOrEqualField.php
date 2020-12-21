<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\LessThanOrEqual;

/**
 * The current field value must be <= to the other field
 */
class LessThanOrEqualField extends LessThanOrEqual
{
    use FieldComparisonTrait;
}
