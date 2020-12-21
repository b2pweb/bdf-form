<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraints\GreaterThan;

/**
 * The current field value must be > to the other field
 */
class GreaterThanField extends GreaterThan
{
    use FieldComparisonTrait;
}
