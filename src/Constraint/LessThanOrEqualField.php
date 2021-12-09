<?php

namespace Bdf\Form\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

/**
 * The current field value must be <= to the other field
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class LessThanOrEqualField extends LessThanOrEqual
{
    use FieldComparisonTrait;
}
