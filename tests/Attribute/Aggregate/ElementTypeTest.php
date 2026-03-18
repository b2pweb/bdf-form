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
use Tests\Form\Attribute\TestCase;

class ElementTypeTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
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

    /**
     * @dataProvider provideAttributesProcessor
     */
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

    /**
     * @dataProvider provideAttributesProcessor
     */
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
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $values = $builder->add('values', ArrayElement::class);
        $values->element(IntegerElement::class, [$form, 'configureField']);
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
