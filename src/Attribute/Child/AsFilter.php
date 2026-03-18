<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\MethodChildBuilderAttributeInterface;
use ReflectionMethod;

/**
 * Define the annotated method as filter for elements
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->filter([$this, 'myTransformer']);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     private IntegerElement $foo;
 *
 *     #[AsFilter('filterFoo')]
 *     public function filterFoo($value, ChildInterface $child, $default): string
 *     {
 *         return hexdec($value);
 *     }
 * }
 * </code>
 *
 * @implements MethodChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChildBuilderInterface::filter() The called method
 * @see ClosureFilter The used filter class
 * @see CallbackTransformer For use transformer in same way, but for http transformer intead of filter one
 * @see CallbackFilter To annotate the property instead
 *
 * @api
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class AsFilter implements MethodChildBuilderAttributeInterface
{
    /**
     * @var list<non-empty-string>
     * @readonly
     */
    private array $targets;

    /**
     * @param non-empty-string ...$targets List of elements names that the attribute can be applied on. Must be a property name.
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
        return new CallbackFilter($method->getName());
    }
}
