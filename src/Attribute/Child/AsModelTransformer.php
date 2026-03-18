<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Processor\MethodChildBuilderAttributeInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use ReflectionMethod;

/**
 * Define the annotated method as model transformer for elements
 *
 * The method should have the following signature :
 *   `function (mixed $value, IntegerElement $input, bool $toEntity): mixed`
 *
 * The `$toEntity` parameter is a boolean that indicates if the transformation is from the input to the entity (true)
 * or from the entity to the input (false)
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->modelTransformer([$this, 'myTransformer']);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     private IntegerElement $bar;
 *
 *     #[AsModelTransformer('bar')]
 *     public function barTransformer($value, IntegerElement $input, bool $toEntity)
 *     {
 *         return $toEntity ? dechex($value) : hexdec($value);
 *     }
 * }
 * </code>
 *
 * @implements MethodChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChildBuilderInterface::modelTransformer() The called method
 * @see ModelTransformer For use a transformer class as model transformer
 * @see CallbackTransformer For use transformer in same way, but for http transformer intead of model one
 * @see CallbackModelTransformer To annotate the property instead
 *
 * @api
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class AsModelTransformer implements MethodChildBuilderAttributeInterface
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
        return new CallbackModelTransformer($method->getName());
    }
}
