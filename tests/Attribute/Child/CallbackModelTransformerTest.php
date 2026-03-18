<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\CallbackModelTransformer;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\TestCase;

class CallbackModelTransformerTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new #[Generates(Struct::class)] class(null, $processor) extends AttributeForm {
            #[CallbackModelTransformer('aTransformer'), Getter, Setter]
            public StringElement $a;
            #[CallbackModelTransformer(toEntity: 'bToEntity', toInput: 'bToInput'), Getter, Setter]
            public IntegerElement $b;

            public function aTransformer($value, StringElement $input, bool $toPhp)
            {
                return $toPhp ? base64_encode($value) : base64_decode($value);
            }

            public function bToEntity($value, IntegerElement $input)
            {
                return dechex($value);
            }

            public function bToInput($value, IntegerElement $input)
            {
                return hexdec($value);
            }
        };

        $form->submit(['a' => 'foo', 'b' => '15']);
        $this->assertEquals(new Struct(a: 'Zm9v', b: 'f'), $form->value());

        $form->import(new Struct(a: 'SGVsbG8gV29ybGQgIQ==', b: 'a'));
        $this->assertEquals('Hello World !', $form->a->value());
        $this->assertEquals(10, $form->b->value());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_with_only_one_transformation_method(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[CallbackModelTransformer(toEntity: 't'), Getter, Setter]
            public IntegerElement $foo;
            #[CallbackModelTransformer(toInput: 't'), Getter, Setter]
            public IntegerElement $bar;

            public function t($value, $input)
            {
                return $value + 1;
            }
        };

        $form->submit(['foo' => '5', 'bar' => '5']);
        $this->assertSame([
            'foo' => 6,
            'bar' => 5
        ], $form->value());

        $form->import(['foo' => 5, 'bar' => 5]);
        $this->assertSame(5, $form->foo->value());
        $this->assertSame(6, $form->bar->value());
    }

    /**
     *
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[CallbackModelTransformer(toEntity: 't'), Getter, Setter]
            public IntegerElement $foo;
            #[CallbackModelTransformer(toInput: 't'), Getter, Setter]
            public IntegerElement $bar;

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
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Transformer\TransformerInterface;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', IntegerElement::class);
        $foo->modelTransformer(new class ($form) implements TransformerInterface {
            /**
             * {@inheritdoc}
             */
            function transformToHttp($value, ElementInterface $input)
            {
                return $value;
            }

            /**
             * {@inheritdoc}
             */
            function transformFromHttp($value, ElementInterface $input)
            {
                return $this->form->t($value, $input);
            }

            public function __construct(
                private $form,
            ) {
            }
        });
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());

        $bar = $builder->add('bar', IntegerElement::class);
        $bar->modelTransformer(new class ($form) implements TransformerInterface {
            /**
             * {@inheritdoc}
             */
            function transformToHttp($value, ElementInterface $input)
            {
                return $this->form->t($value, $input);
            }

            /**
             * {@inheritdoc}
             */
            function transformFromHttp($value, ElementInterface $input)
            {
                return $value;
            }

            public function __construct(
                private $form,
            ) {
            }
        });
        $bar->extractor(new Getter());
        $bar->hydrator(new Setter());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
        $form->bar = $inner['bar']->element();
    }
}

PHP
        , $form
);
    }
}
