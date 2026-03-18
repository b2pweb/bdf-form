<?php

namespace Bdf\Form\Attribute\Element;

use Attribute;
use Bdf\Form\Attribute\Child\AsModelTransformer;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\MethodChildBuilderAttributeInterface;
use Bdf\Form\ElementBuilderInterface;
use ReflectionMethod;

/**
 * Define the annotated method as a transformer for elements
 *
 *  The method should have the following signature :
 *    `function (mixed $value, IntegerElement $input, bool $toPhp): mixed`
 *
 *  The `$toPhp` parameter is a boolean that indicates if the transformation is from the http value to php input value (true)
 *  or from the php input value to the http value (false)
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->transformer([$this, 'myTransformer']);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     private IntegerElement $bar;
 *
 *     #[AsTransformer('bar')]
 *     public function barTransformer($value, IntegerElement $input, bool $toPhp)
 *     {
 *         return $toPhp ? hexdec($value) : dechex($value);
 *     }
 * }
 * </code>
 *
 * @implements MethodChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ElementBuilderInterface::transformer() The called method
 * @see Transformer For use a transformer class as transformer
 * @see AsModelTransformer For use transformer in same way, but for model transformer intead of http one
 * @see CallbackTransformer To annotate the property instead
 *
 * @api
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class AsTransformer implements MethodChildBuilderAttributeInterface
{
    /**
     * @var list<non-empty-string>
     * @readonly
     */
    private array $targets;

    /**
     * @param non-empty-string ...$targets Target elements properties names
     * @no-named-arguments
     */
    public function __construct(string ...$targets)
    {
        $this->targets = $targets;
    }

    /**
     * {@inheritdoc}
     */
    public function targetElements(): array
    {
        return $this->targets;
    }

    /**
     * {@inheritdoc}
     */
    public function attribute(ReflectionMethod $method): ChildBuilderAttributeInterface
    {
        return new CallbackTransformer($method->getName());
    }
}
