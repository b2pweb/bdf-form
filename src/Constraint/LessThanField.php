<?php

namespace Bdf\Form\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraints\LessThan;

/**
 * The current field value must be < to the other field
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class LessThanField extends LessThan
{
    use FieldComparisonTrait;
}
