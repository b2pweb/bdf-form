<?php

namespace Bdf\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class StructElementTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(processor: $processor) extends AttributeForm {
            #[StructElement(InnerElementStruct::class), Setter]
            public ArrayElement $values;
        };

        $form->submit(['values' => [
            ['id' => 1],
            ['id' => 2],
        ]]);

        $this->assertTrue($form->valid());
        $this->assertEquals([
            new InnerElementStruct(1),
            new InnerElementStruct(2),
        ], $form->value()['values']);
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestStructElementStruct::class, processor: $processor);

        $form->submit(['values' => [
            ['id' => 1],
            ['id' => 2],
        ]]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new TestStructElementStruct([
            new InnerElementStruct(1),
            new InnerElementStruct(2),
        ]), $form->value());
    }

    #[Test]
    public function codeGeneration()
    {
        $form = new class extends AttributeForm {
            #[StructElement(InnerElementStruct::class), Setter]
            public ArrayElement $values;
        };

        $this->assertGenerated(<<<'PHP'
        namespace Generated;
        
        use Bdf\Form\Aggregate\ArrayElement;
        use Bdf\Form\Aggregate\FormBuilderInterface;
        use Bdf\Form\Aggregate\FormInterface;
        use Bdf\Form\Attribute\AttributeForm;
        use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
        use Bdf\Form\Attribute\Processor\PostConfigureInterface;
        use Bdf\Form\PropertyAccess\Setter;
        
        class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
        {
            /**
             * {@inheritdoc}
             */
            function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
            {
                $values = $builder->add('values', ArrayElement::class);
                $values->struct('Bdf\Form\Attribute\Aggregate\InnerElementStruct');
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

        PHP, $form);
    }

    #[Test]
    public function codeGenerationStruct()
    {
        $this->assertGeneratedStruct(<<<'PHP'
        namespace Generated;
        
        use Bdf\Form\Aggregate\ArrayElement;
        use Bdf\Form\Aggregate\FormBuilderInterface;
        use Bdf\Form\Attribute\Aggregate\TestStructElementStruct;
        use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
        use Bdf\Form\Attribute\Processor\PostConfigureInterface;
        use Bdf\Form\PropertyAccess\Setter;
        
        class GeneratedConfigurator implements AttributesProcessorInterface
        {
            /**
             * {@inheritdoc}
             */
            function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
            {
                $builder->generates(TestStructElementStruct::class);

                $values = $builder->add('values', ArrayElement::class);
                $values->struct('Bdf\Form\Attribute\Aggregate\InnerElementStruct');
                $values->hydrator(new Setter());
        
                return null;
            }
        }

        PHP, TestStructElementStruct::class);
    }
}

class InnerElementStruct
{
    public function __construct(
        public int $id,
    ) {}
}

class TestStructElementStruct
{
    public function __construct(
        #[StructElement(InnerElementStruct::class), Setter]
        public array $values,
    ) {}
}
