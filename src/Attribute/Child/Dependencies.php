<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Override;

/**
 * Add dependencies on other sibling elements
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->float('foo')->depends('bar', 'baz');
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Dependencies('bar', 'rab')]
 *     private FloatElement $foo;
 *     private IntegerElement $bar;
 *     private StringElement $rab;
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChildBuilderInterface::depends() The called method
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Dependencies implements ChildBuilderAttributeInterface
{
    /**
     * @var list<string>
     * @readonly
     */
    private array $dependencies;

    /**
     * @param string ...$dependencies List of inputs names
     * @no-named-arguments
     */
    public function __construct(string ...$dependencies)
    {
        $this->dependencies = $dependencies;
    }

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        $builder->depends(...$this->dependencies);
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        $generator->line('$?->depends(...?);', [$name, $this->dependencies]);
    }
}
