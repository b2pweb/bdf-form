<?php

namespace Bdf\Form\Phone;

use Attribute;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * NotBlank implementation for PhoneNumber value
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class NotEmptyPhoneNumber extends NotBlank
{

}
