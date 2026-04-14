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
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

use function base64_decode;
use function base64_encode;

class AsModelTransformerTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
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

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestAsModelTransformerStruct::class, processor: $processor);

        $form->submit(['a' => 'foo']);
        $this->assertEquals(new TestAsModelTransformerStruct(a: 'Zm9v'), $form->value());

        $form->import(new TestAsModelTransformerStruct(a: 'SGVsbG8gV29ybGQgIQ=='));
        $this->assertEquals('Hello World !', $form['a']->element()->value());
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
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', IntegerElement::class);
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());
        $foo->modelTransformer($context->t(...));

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
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Child\TestAsModelTransformerStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestAsModelTransformerStruct::class);

        $a = $builder->add('a', StringElement::class);
        $a->extractor(new Getter());
        $a->hydrator(new Setter());
        $a->required(null);
        $a->modelTransformer(TestAsModelTransformerStruct::aTransformer(...));

        return null;
    }
}

PHP
        , TestAsModelTransformerStruct::class
);
    }
}

class TestAsModelTransformerStruct
{
    public function __construct(
        #[Getter, Setter]
        public string $a,
    ) {}

    #[AsModelTransformer('a')]
    public static function aTransformer($value, StringElement $input, bool $toPhp)
    {
        return $toPhp ? base64_encode($value) : base64_decode($value);
    }
}
