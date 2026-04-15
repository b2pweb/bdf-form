<?php

namespace Bdf\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\GetSet;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Custom\CustomForm;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class OptionalTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(processor: $processor) extends AttributeForm {
            #[Optional, Setter]
            public InnerElementForm $inner;
        };

        $form->submit([]);
        $this->assertTrue($form->valid());
        $this->assertNull($form->value()['inner']);

        $form->submit(['inner' => ['id' => 42]]);
        $this->assertTrue($form->valid());
        $this->assertEquals(['id' => 42], $form->value()['inner']);
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestOptionalStruct::class, processor: $processor);

        $form->submit(['values' => [
            ['id' => 1],
            ['id' => 2],
        ]]);

        $form->submit([]);
        $this->assertTrue($form->valid());
        $this->assertEquals(new TestOptionalStruct(null), $form->value());

        $form->submit(['inner' => ['id' => 42]]);
        $this->assertTrue($form->valid());

        $this->assertEquals(new TestOptionalStruct(new InnerElementStruct2(42)), $form->value());
    }

    #[Test]
    public function codeGeneration()
    {
        $form = new class extends AttributeForm {
            #[Optional, Setter]
            public InnerElementForm $inner;
        };

        $this->assertGenerated(<<<'PHP'
        namespace Generated;
        
        use Bdf\Form\Aggregate\FormBuilderInterface;
        use Bdf\Form\Aggregate\FormInterface;
        use Bdf\Form\Attribute\Aggregate\InnerElementForm;
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
                $inner = $builder->add('inner', InnerElementForm::class);
                $inner->optional();
                $inner->hydrator(new Setter());
        
                return $this;
            }
        
            /**
             * {@inheritdoc}
             */
            function postConfigure(AttributeForm $form, FormInterface $inner): void
            {
                $form->inner = $inner['inner']->element();
            }
        }

        PHP, $form);
    }

    #[Test]
    public function codeGenerationStruct()
    {
        $this->assertGeneratedStruct(<<<'PHP'
        namespace Generated;
        
        use Bdf\Form\Aggregate\FormBuilderInterface;
        use Bdf\Form\Attribute\Aggregate\TestOptionalStruct;
        use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
        use Bdf\Form\Attribute\Processor\PostConfigureInterface;
        use Bdf\Form\PropertyAccess\Getter;
        use Bdf\Form\PropertyAccess\Setter;
        use Bdf\Form\Struct\StructForm;
        
        class GeneratedConfigurator implements AttributesProcessorInterface
        {
            /**
             * {@inheritdoc}
             */
            function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
            {
                $builder->generates(TestOptionalStruct::class);

                $inner = $builder->add('inner', StructForm::class);
                $inner->optional();
                $inner->hydrator(new Setter(null))->extractor(new Getter(null));
                $inner->class('Bdf\Form\Attribute\Aggregate\InnerElementStruct2');
        
                return null;
            }
        }

        PHP, TestOptionalStruct::class);
    }
}

class InnerElementForm extends AttributeForm
{
    #[GetSet]
    public IntegerElement $id;
}

class InnerElementStruct2
{
    public function __construct(
        public int $id,
    ) {}
}

class TestOptionalStruct
{
    public function __construct(
        #[Optional, GetSet]
        public ?InnerElementStruct2 $inner,
    ) {}
}
