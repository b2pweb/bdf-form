<?php

namespace Bdf\Form\Attribute\Element;

use Attribute;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Attribute\Aggregate\ArrayTransformer;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Nette\PhpGenerator\Literal;

/**
 * Add a transformer on the element, using a transformer class
 *
 * Note: it's preferred to use directly the transformer as attribute on the element property
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->transformer(new MyTransformer(...$arguments));
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Transformer(MyTransformer::class, ['foo', 'bar'])]
 *     private IntegerElement $foo;
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ElementBuilderInterface::transformer() The called method
 * @see ArrayElementBuilder::arrayTransformer() The called method if array flag is set
 * @see CallbackTransformer For use custom methods as transformer instead of class
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Transformer implements ChildBuilderAttributeInterface
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
        /**
         * Apply the transformer on the whole array element
         * instead of each element
         *
         * If set to true, {@see ArrayElementBuilder::arrayTransformer()} will be used
         *
         * Note: this flag can be used only on array element
         *
         * @var bool
         * @readonly
         *
         * @see ArrayTransformer Prefer use this attribute for array element, instead of manually set this flag
         */
        private bool $array = false,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $transformer = new $this->transformerClass(...$this->constructorArguments);

        if ($this->array) {
            /** @var ChildBuilderInterface<ArrayElementBuilder> $builder */
            $builder->arrayTransformer($transformer);
        } else {
            $builder->transformer($transformer);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $transformer = $generator->useAndSimplifyType($this->transformerClass);
        $code = $this->array ? '$?->arrayTransformer(new ?(...?));' : '$?->transformer(new ?(...?));';

        $generator->line($code, [$name, new Literal($transformer), $this->constructorArguments]);
    }
}
