<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\AsModelTransformer;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\TestCase;

class AsModelTransformerTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new #[Generates(Struct::class)] class(null, $processor) extends AttributeForm {
            #[Getter, Setter]
            public StringElement $a;

            #[AsModelTransformer('a')]
            public function aTransformer($value, StringElement $input, bool $toPhp)
            {
                return $toPhp ? base64_encode($value) : base64_decode($value);
            }
        };

        $form->submit(['a' => 'foo']);
        $this->assertEquals(new Struct(a: 'Zm9v'), $form->value());

        $form->import(new Struct(a: 'SGVsbG8gV29ybGQgIQ=='));
        $this->assertEquals('Hello World !', $form->a->value());
    }

    /**
     *
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Getter, Setter]
            public IntegerElement $foo;

            #[AsModelTransformer('foo')]
            public function t($value, $input)
            {
                return $value + 1;
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', IntegerElement::class);
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());
        $foo->modelTransformer([$form, 't']);

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
        , $form
);
    }
}
