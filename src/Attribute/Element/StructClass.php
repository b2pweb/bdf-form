<?php

namespace Bdf\Form\Attribute\Element;

use Attribute;
use Bdf\Form\Attribute\Aggregate\StructElement;
use Bdf\Form\Attribute\BuilderMethodCall;
use Bdf\Form\Struct\StructForm;
use Bdf\Form\Struct\StructFormBuilder;

/**
 * Define the struct class for a {@see StructForm}
 *
 * @see StructFormBuilder::class() The called method
 * @see StructElement To define the type of array
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class StructClass extends BuilderMethodCall
{
    /**
     * @param class-string $className The struct class name
     */
    public function __construct(string $className)
    {
        parent::__construct('class', [$className]);
    }
}
