<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\ElementType;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\DefaultValue;
use Bdf\Form\Attribute\Child\HttpField;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class HttpFieldTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[HttpField('_v_')]
            public IntegerElement $v;
        };

        $form->submit(['_v_' => 42]);
        $this->assertSame(42, $form->v->value());
        $this->assertSame(['_v_' => '42'], $form->httpValue());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestHttpFieldStruct::class, processor: $processor);

        $form->submit(['_v_' => 42]);
        $this->assertSame(42, $form->value()->v);
        $this->assertSame(['_v_' => '42'], $form->httpValue());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[HttpField('_v_')]
            public IntegerElement $v;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Child\Http\ArrayOffsetHttpFields;
use Bdf\Form\Leaf\IntegerElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $v = $builder->add('v', IntegerElement::class);
        $v->httpFields(new ArrayOffsetHttpFields('_v_'));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->v = $inner['v']->element();
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

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Child\Http\ArrayOffsetHttpFields;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Child\TestHttpFieldStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestHttpFieldStruct::class);

        $v = $builder->add('v', IntegerElement::class);
        $v->httpFields(new ArrayOffsetHttpFields('_v_'));
        $v->hydrator(new Setter(null))->extractor(new Getter(null));
        $v->required(null);

        return null;
    }
}

PHP
            , TestHttpFieldStruct::class);
    }
}

class TestHttpFieldStruct
{
    #[HttpField('_v_')]
    public int $v;
}
