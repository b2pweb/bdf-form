<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;

/**
 * Define simple hydrator and extractor on the element, using the name property or accessor name
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->float('foo')->getter('bar')->setter('bar');
 * $builder->float('foo')->hydrator(new Setter('bar'))->extract(new Getter('bar'));
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[GetSet('bar')]
 *     private FloatElement $foo;
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChildBuilder::getter()
 * @see ChildBuilder::setter()
 * @see ChildBuilderInterface::hydrator()
 * @see ChildBuilderInterface::extractor()
 *
 * @see Getter For define only the extractor
 * @see Setter For define only the hydrator
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class GetSet implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The property name to use
         * This can be a public property, or public accessor method
         * (optionally starting with get for the getter, and starting with set for the setter)
         *
         * If not provided, the input name will be used as property name
         *
         * @readonly
         */
        private ?string $propertyName = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->hydrator(new Setter($this->propertyName))->extractor(new Getter($this->propertyName));
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->use(Setter::class)->use(Getter::class);
        $generator->line('$?->hydrator(new Setter(?))->extractor(new Getter(?));', [$name, $this->propertyName, $this->propertyName]);
    }
}
