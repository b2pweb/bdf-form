<?php

namespace Bdf\Form\Attribute\Form;

use Attribute;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;

/**
 * Define the value generator of the form, using a class name
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->generates(MyEntity::class);
 * </code>
 *
 * Usage:
 * <code>
 * #[Generates(MyEntity::class)]
 * class MyForm extends AttributeForm
 * {
 *     // ...
 * }
 * </code>
 *
 * @see FormBuilderInterface::generates() The called method
 * @see ValueGenerator
 * @see CallbackGenerator For generate using a custom method
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Generates implements FormBuilderAttributeInterface
{
    public function __construct(
        /**
         * The entity class name to generate
         *
         * @var class-string
         * @readonly
         */
        private string $className,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnFormBuilder(AttributeForm $form, FormBuilderInterface $builder): void
    {
        $builder->generates($this->className);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForFormBuilder(AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line(
            '$builder->generates(?::class);',
            [
                new Literal($generator->useAndSimplifyType($this->className))
            ]
        );
    }
}
