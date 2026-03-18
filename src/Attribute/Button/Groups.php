<?php

namespace Bdf\Form\Attribute\Button;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\RootElementInterface;

/**
 * Attribute for define the validation group to use when the related button is clicked
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->button('btn')->groups(['Foo', 'Bar']);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Groups('Foo', 'Bar')]
 *     private ButtonInterface $btn;
 * }
 * </code>
 *
 * @see ButtonBuilderInterface::groups() The called method
 * @see ButtonInterface::constraintGroups() Modify this value
 * @see RootElementInterface::constraintGroups()
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Groups implements ButtonBuilderAttributeInterface
{
    /**
     * @var list<string>
     * @readonly
     */
    private array $groups;

    /**
     * @param string ...$groups List of validation groups
     * @no-named-arguments
     */
    public function __construct(string ...$groups)
    {
        $this->groups = $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnButtonBuilder(AttributeForm $form, ButtonBuilderInterface $builder): void
    {
        $builder->groups($this->groups);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForButtonBuilder(AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line('    ->groups(?)', [$this->groups]);
    }
}
