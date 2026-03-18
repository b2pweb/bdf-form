<?php

namespace Bdf\Form\Attribute\Aggregate;

use Attribute;
use Bdf\Form\Attribute\Element\Transformer;
use Bdf\Form\Transformer\TransformerInterface;

/**
 * Add a transformer on the array element, using a transformer class
 *
 * This attribute will simply set the array flag to true on the {@see Transformer} attribute.
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->arrayTransformer(new MyTransformer(...$arguments));
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[ArrayTransformer(MyTransformer::class, ['foo', 'bar']), ElementType(IntegerElement::class)]
 *     private ArrayElement $foo;
 * }
 * </code>
 *
 * @see ArrayElementBuilder::arrayTransformer() The called method
 * @see CallbackTransformer For use custom methods as transformer instead of class
 * @see Transformer To add a transformer the item of the array
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ArrayTransformer extends Transformer
{
    /**
     * @param class-string<TransformerInterface> $transformerClass The transformer class name
     * @param array $constructorArguments Arguments to provide on the transformer constructor
     */
    public function __construct(string $transformerClass, array $constructorArguments = [])
    {
        parent::__construct($transformerClass, $constructorArguments, true);
    }
}
