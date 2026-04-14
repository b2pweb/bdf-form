<?php

namespace Bdf\Form\Attribute\Aggregate;

use Attribute;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Attribute\BuilderMethodCall;
use Bdf\Form\Attribute\Element\StructClass;

/**
 * Define the array element type as struct with the given class
 *
 * @see ArrayElementBuilder::struct() The called method
 * @see StructClass To define the struct class on a simple property
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class StructElement extends BuilderMethodCall
{
    /**
     * @param class-string $className The struct class name
     */
    public function __construct(string $className)
    {
        parent::__construct('struct', [$className]);
    }
}
