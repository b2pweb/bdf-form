<?php

namespace Bdf\Form\Attribute\Element\Date;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;

/**
 * Define that the element date must be after the date of the other field
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->dateTime('dateEnd')->afterField('dateStart');
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     private DateTimeElement $dateStart;
 *
 *     #[Dependencies('dateStart'), AfterField('dateStart')]
 *     private DateTimeElement $dateEnd;
 * }
 * </code>
 *
 * @see \Bdf\Form\Leaf\Date\DateTimeElementBuilder::afterField() The called method
 * @see BeforeField For the opposite constraint
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\Leaf\Date\DateTimeElementBuilder>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class AfterField implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The field name to compare
         *
         * @var non-empty-string
         * @readonly
         */
        private string $field,
        /**
         * The error message.
         * If not set, the default message will be used
         *
         * @var string|null
         */
        private ?string $message = null,
        /**
         * If true, will allow the date to be equal to the other field
         *
         * @var bool
         */
        private bool $orEqual = false,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->afterField($this->field, $this->message, $this->orEqual);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line('$?->afterField(?, ?, ?);', [$name, $this->field, $this->message, $this->orEqual]);
    }
}
