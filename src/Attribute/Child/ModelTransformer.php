<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Nette\PhpGenerator\Literal;

/**
 * Add a model transformer on the child, using a transformer class
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->modelTransformer(new MyTransformer(...$arguments));
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[ModelTransformer(MyTransformer::class, ['foo', 'bar'])]
 *     private IntegerElement $foo;
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChildBuilderInterface::modelTransformer() The called method
 * @see CallbackModelTransformer For use custom methods as transformer instead of class
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class ModelTransformer implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The transformer class name
         *
         * @var class-string<TransformerInterface>
         * @readonly
         */
        private string $transformerClass,
        /**
         * Arguments to provide on the transformer constructor
         *
         * @var array
         * @readonly
         */
        private array $constructorArguments = [],
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->modelTransformer(new $this->transformerClass(...$this->constructorArguments));
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $transformer = $generator->useAndSimplifyType($this->transformerClass);
        $generator->line('$?->modelTransformer(new ?(...?));', [$name, new Literal($transformer), $this->constructorArguments]);
    }
}
