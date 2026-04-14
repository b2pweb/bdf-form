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
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class CallbackModelTransformerTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
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

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestCallbackModelTransformerStruct::class, processor: $processor);

        $form->submit(['a' => 'foo', 'b' => '15']);
        $this->assertEquals(new TestCallbackModelTransformerStruct(a: 'Zm9v', b: 'f'), $form->value());

        $form->import(new TestCallbackModelTransformerStruct(a: 'SGVsbG8gV29ybGQgIQ==', b: 'a'));
        $this->assertEquals('Hello World !', $form['a']->element()->value());
        $this->assertEquals(10, $form['b']->element()->value());
    }

    #[DataProvider('provideAttributesProcessor')]
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

    #[DataProvider('provideStructAttributesProcessor')]
    public function test_with_only_one_transformation_method_struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestCallbackModelTransformerOneMethodStruct::class, processor: $processor);

        $form->submit(['foo' => '5', 'bar' => '5']);
        $this->assertEquals(new TestCallbackModelTransformerOneMethodStruct(foo: 6, bar: 5), $form->value());

        $form->import(new TestCallbackModelTransformerOneMethodStruct(foo: 5, bar: 5));
        $this->assertEquals(5, $form['foo']->element()->value());
        $this->assertEquals(6, $form['bar']->element()->value());
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
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', IntegerElement::class);
        $foo->modelTransformer(new class ($context) implements TransformerInterface {
            /**
             * {@inheritdoc}
             */
            function transformToHttp(mixed $value, ElementInterface $input): mixed
            {
                return $value;
            }

            /**
             * {@inheritdoc}
             */
            function transformFromHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context->t($value, $input);
            }

            public function __construct(
                private $context,
            ) {
            }
        });
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());

        $bar = $builder->add('bar', IntegerElement::class);
        $bar->modelTransformer(new class ($context) implements TransformerInterface {
            /**
             * {@inheritdoc}
             */
            function transformToHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context->t($value, $input);
            }

            /**
             * {@inheritdoc}
             */
            function transformFromHttp(mixed $value, ElementInterface $input): mixed
            {
                return $value;
            }

            public function __construct(
                private $context,
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

    /**
     *
     */
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
use Tests\Form\Attribute\Child\TestCallbackModelTransformerStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestCallbackModelTransformerStruct::class);

        $a = $builder->add('a', StringElement::class);
        $a->modelTransformer(TestCallbackModelTransformerStruct::aTransformer(...));
        $a->extractor(new Getter());
        $a->hydrator(new Setter());
        $a->required(null);

        $b = $builder->add('b', StringElement::class);
        $b->modelTransformer(new class ($context) implements TransformerInterface {
            /**
             * {@inheritdoc}
             */
            function transformToHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context::bToInput($value, $input);
            }

            /**
             * {@inheritdoc}
             */
            function transformFromHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context::bToEntity($value, $input);
            }

            public function __construct(
                private $context,
            ) {
            }
        });
        $b->extractor(new Getter());
        $b->hydrator(new Setter());
        $b->required(null);

        return null;
    }
}

PHP
        , TestCallbackModelTransformerStruct::class
);
    }
}

class TestCallbackModelTransformerStruct
{
    public function __construct(
        #[CallbackModelTransformer('aTransformer'), Getter, Setter]
        public string $a,

        #[CallbackModelTransformer(toEntity: 'bToEntity', toInput: 'bToInput'), Getter, Setter]
        public string $b,
    ) {}

    public static function aTransformer($value, StringElement $input, bool $toPhp)
    {
        return $toPhp ? base64_encode($value) : base64_decode($value);
    }

    public static function bToEntity($value, StringElement $input)
    {
        return dechex($value);
    }

    public static function bToInput($value, StringElement $input)
    {
        return hexdec($value);
    }
}

class TestCallbackModelTransformerOneMethodStruct
{
    public function __construct(
        #[CallbackModelTransformer(toEntity: 't'), Getter, Setter]
        public int $foo,

        #[CallbackModelTransformer(toInput: 't'), Getter, Setter]
        public int $bar,
    ) {}

    public static function t($value, $input)
    {
        return $value + 1;
    }
}
