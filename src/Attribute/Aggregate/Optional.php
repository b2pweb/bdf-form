<?php

namespace Bdf\Form\Attribute\Aggregate;

use Attribute;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\BuilderMethodCall;

/**
 * Define the embedded form element as optional
 *
 * @see FormBuilderInterface::optional() The called method
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Optional extends BuilderMethodCall
{
    public function __construct()
    {
        parent::__construct('optional', []);
    }
}
