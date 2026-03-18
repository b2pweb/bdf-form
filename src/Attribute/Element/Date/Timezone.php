<?php

namespace Bdf\Form\Attribute\Element\Date;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;

/**
 * Define the time used for parse and convert the date
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->dateTime('date')->timezone('Europe/Paris');
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Timezone('Europe/Paris')]
 *     private DateTimeElement $foo;
 * }
 * </code>
 *
 * @see \Bdf\Form\Leaf\Date\DateTimeElementBuilder::timezone() The called method
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\Leaf\Date\DateTimeElementBuilder>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Timezone implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The timezone name or offset
         *
         * @var non-empty-string
         * @readonly
         */
        private string $timezone,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->timezone($this->timezone);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line('$?->timezone(?);', [$name, $this->timezone]);
    }
}
