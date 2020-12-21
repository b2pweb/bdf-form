<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\LessThan;

/**
 * The current field value must be < to the other field
 */
class LessThanField extends LessThan
{
    use FieldComparisonTrait;
}
