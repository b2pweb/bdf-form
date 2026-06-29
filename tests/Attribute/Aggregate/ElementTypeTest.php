<?php

namespace Tests\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\ElementType;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class ElementTypeTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test_simple(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[ElementType(IntegerElement::class), Setter]
            public ArrayElement $values;
        };

        $form->submit(['values' => ['123', '456', '789']]);
        $this->assertTrue($form->valid());

        $this->assertSame(['values' => [123, 456, 789]], $form->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function simple_struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestElementTypeSimpleStruct::class, processor: $processor);

        $form->submit(['values' => ['123', '456', '789']]);
        $this->assertTrue($form->valid());

        $this->assertSame([123, 456, 789], $form->value()->values);
    }

    #[DataProvider('provideAttributesProcessor')]
    public function test_with_configurator(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[ElementType(IntegerElement::class, "configureField"), Setter]
            public ArrayElement $values;

            public function configureField(IntegerElementBuilder $builder): void
            {
                $builder->min(200);
            }
        };

        $form->submit(['values' => ['123', '456', '789']]);
        $this->assertFalse($form->valid());

        $this->assertEquals(['values' => [0 => 'This value should be greater than or equal to 200.']], $form->error()->toArray());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function test_with_configurator_struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestElementTypeConfiguratorStruct::class, processor: $processor);

        $form->submit(['values' => ['123', '456', '789']]);
        $this->assertFalse($form->valid());

        $this->assertEquals(['values' => [0 => 'This value should be greater than or equal to 200.']], $form->error()->toArray());
    }

    #[DataProvider('provideAttributesProcessor')]
    public function test_with_embedded(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[ElementType(EmbeddedForm::class), Setter]
            public ArrayElement $values;
        };

        $form->submit(['values' => [['a' => 'az', 'b' => 'er'], ['a' => 'ty', 'b' => 'ui']]]);
        $this->assertTrue($form->valid());

        $this->assertEquals(['values' => [new Struct('az', 'er'), new Struct('ty', 'ui')]], $form->value());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[ElementType(IntegerElement::class, "configureField"), Setter]
            public ArrayElement $values;

            public function configureField(IntegerElementBuilder $builder): void
            {
                $builder->min(200);
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\PropertyAccess\Setter;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $values = $builder->add('values', ArrayElement::class);
        $values->element(IntegerElement::class, $context->configureField(...));
        $values->hydrator(new Setter());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->values = $inner['values']->element();
    }
}

PHP
            , $form);
    }

    /**
     * @return void
     */
    public function test_code_generator_struct()
    {
        $this->assertGeneratedStruct(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Aggregate\TestElementTypeConfiguratorStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestElementTypeConfiguratorStruct::class);

        $values = $builder->add('values', ArrayElement::class);
        $values->element(IntegerElement::class, TestElementTypeConfiguratorStruct::configureField(...));
        $values->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
            , TestElementTypeConfiguratorStruct::class);
    }
}

#[Generates(Struct::class)]
class EmbeddedForm extends AttributeForm
{
    #[Setter]
    public StringElement $a;
    #[Setter]
    public StringElement $b;
}

class Struct
{
    public function __construct(
        public ?string $a = null,
        public ?string $b = null,
    ) {}
}

class TestElementTypeSimpleStruct
{
    #[ElementType(IntegerElement::class)]
    public array $values;
}

class TestElementTypeConfiguratorStruct
{
    #[ElementType(IntegerElement::class, "configureField")]
    public array $values;

    public static function configureField(IntegerElementBuilder $builder): void
    {
        $builder->min(200);
    }
}
