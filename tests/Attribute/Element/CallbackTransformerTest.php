<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\GetSet;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class CallbackTransformerTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[CallbackTransformer('fooTransformer')]
            public StringElement $foo;

            #[CallbackTransformer(fromHttp: 'inTransformer', toHttp: 'outTransformer')]
            public StringElement $bar;

            public function fooTransformer($value, StringElement $input, bool $toPhp)
            {
                return json_encode([$value, $toPhp]);
            }

            public function inTransformer($value, StringElement $input)
            {
                return json_encode(['in', $value]);
            }

            public function outTransformer($value, StringElement $input)
            {
                return json_encode(['out', $value]);
            }
        };

        $form->submit(['foo' => 'a', 'bar' => 'b']);

        $this->assertEquals('["a",true]', $form->foo->value());
        $this->assertEquals('["in","b"]', $form->bar->value());

        $view = $form->view();

        $this->assertEquals('["[\"a\",true]",false]', $view['foo']->value());
        $this->assertEquals('["out","[\"in\",\"b\"]"]', $view['bar']->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestCallbackTransformerStruct::class, processor: $processor);

        $form->submit(['foo' => 'a', 'bar' => 'b']);

        $this->assertEquals('["a",true]', $form->value()->foo);
        $this->assertEquals('["in","b"]', $form->value()->bar);

        $view = $form->view();

        $this->assertEquals('["[\"a\",true]",false]', $view['foo']->value());
        $this->assertEquals('["out","[\"in\",\"b\"]"]', $view['bar']->value());
    }
    
    #[DataProvider('provideAttributesProcessor')]
    public function test_with_only_one_transformation_method(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[CallbackTransformer(fromHttp: 't'), GetSet]
            public IntegerElement $foo;
            #[CallbackTransformer(toHttp: 't'), GetSet]
            public IntegerElement $bar;

            public function t($value, $input)
            {
                return $value + 1;
            }
        };

        $form->submit(['foo' => '5', 'bar' => '5']);
        $this->assertSame(6, $form->foo->value());
        $this->assertSame(5, $form->bar->value());

        $form->foo->import(5);
        $form->bar->import(5);

        $view = $form->view();
        $this->assertEquals(5, $view['foo']->value());
        $this->assertEquals(6, $view['bar']->value());
    }

    public function test_code_generator()
    {
        $form = new class() extends AttributeForm {
            #[CallbackTransformer('fooTransformer')]
            public StringElement $foo;

            #[CallbackTransformer(fromHttp: 'inTransformer', toHttp: 'outTransformer')]
            public StringElement $bar;

            public function fooTransformer($value, StringElement $input, bool $toPhp)
            {
                return json_encode([$value, $toPhp]);
            }

            public function inTransformer($value, StringElement $input)
            {
                return json_encode(['in', $value]);
            }

            public function outTransformer($value, StringElement $input)
            {
                return json_encode(['out', $value]);
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
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Transformer\TransformerInterface;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->transformer($context->fooTransformer(...));

        $bar = $builder->add('bar', StringElement::class);
        $bar->transformer(new class ($context) implements TransformerInterface {
            /**
             * {@inheritdoc}
             */
            function transformToHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context->outTransformer($value, $input);
            }

            /**
             * {@inheritdoc}
             */
            function transformFromHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context->inTransformer($value, $input);
            }

            public function __construct(
                private $context,
            ) {
            }
        });

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
        , $form);
    }

    public function test_code_generator_struct()
    {
        $this->assertGeneratedStruct(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Transformer\TransformerInterface;
use Tests\Form\Attribute\Element\TestCallbackTransformerStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestCallbackTransformerStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->transformer(TestCallbackTransformerStruct::fooTransformer(...));
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));

        $bar = $builder->add('bar', StringElement::class);
        $bar->transformer(new class ($context) implements TransformerInterface {
            /**
             * {@inheritdoc}
             */
            function transformToHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context::outTransformer($value, $input);
            }

            /**
             * {@inheritdoc}
             */
            function transformFromHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context::inTransformer($value, $input);
            }

            public function __construct(
                private $context,
            ) {
            }
        });
        $bar->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
        , TestCallbackTransformerStruct::class);
    }
}

class TestCallbackTransformerStruct
{
    #[CallbackTransformer('fooTransformer')]
    public ?string $foo;

    #[CallbackTransformer(fromHttp: 'inTransformer', toHttp: 'outTransformer')]
    public ?string $bar;

    public static function fooTransformer($value, StringElement $input, bool $toPhp)
    {
        return json_encode([$value, $toPhp]);
    }

    public static function inTransformer($value, StringElement $input)
    {
        return json_encode(['in', $value]);
    }

    public static function outTransformer($value, StringElement $input)
    {
        return json_encode(['out', $value]);
    }
}
