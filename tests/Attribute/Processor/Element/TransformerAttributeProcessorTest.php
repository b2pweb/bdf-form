<?php

namespace Tests\Form\Attribute\Processor\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\Element\TransformerAttributeProcessor;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Transformer\TransformerInterface;
use Tests\Form\Attribute\TestCase;

class TransformerAttributeProcessorTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[ATransformer, BTransformer]
            public StringElement $foo;
        };

        $form->submit(['foo' => 'azerty']);
        $this->assertEquals('azertyBA', $form->foo->value());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[ATransformer, BTransformer]
            public StringElement $foo;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;
use Tests\Form\Attribute\Processor\Element\ATransformer;
use Tests\Form\Attribute\Processor\Element\BTransformer;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->transformer(new ATransformer());
        $foo->transformer(new BTransformer());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
    }
}

PHP
            , $form);
    }
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ATransformer implements TransformerInterface
{
    public function transformToHttp($value, ElementInterface $input)
    {

    }

    public function transformFromHttp($value, ElementInterface $input)
    {
        return $value . 'A';
    }
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class BTransformer implements TransformerInterface
{
    public function transformToHttp($value, ElementInterface $input)
    {

    }

    public function transformFromHttp($value, ElementInterface $input)
    {
        return $value . 'B';
    }
}
