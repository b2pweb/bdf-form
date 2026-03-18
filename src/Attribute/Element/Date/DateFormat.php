<?php

namespace Bdf\Form\Attribute\Element\Date;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;

/**
 * Define the date format to use for parse the date string
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->dateTime('date')->format('d/m/Y H:i');
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[DateFormat('d/m/Y H:i')]
 *     private DateTimeElement $foo;
 * }
 * </code>
 *
 * @see \Bdf\Form\Leaf\Date\DateTimeElementBuilder::format() The called method
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\Leaf\Date\DateTimeElementBuilder>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DateFormat implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The date format
         *
         * @var non-empty-string
         * @readonly
         *
         * @see https://www.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters For the format
         */
        private string $format,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->format($this->format);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line('$?->format(?);', [$name, $this->format]);
    }
}
