<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\AsTransformer;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

use function json_encode;

class AsTransformerTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            public StringElement $foo;

            #[AsTransformer('foo')]
            public function fooTransformer($value, StringElement $input, bool $toPhp)
            {
                return json_encode([$value, $toPhp]);
            }
        };

        $form->submit(['foo' => 'a']);

        $this->assertEquals('["a",true]', $form->foo->value());

        $view = $form->view();

        $this->assertEquals('["[\"a\",true]",false]', $view['foo']->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestAsTransformerStruct::class, processor: $processor);

        $form->submit(['foo' => 'a']);

        $this->assertEquals('["a",true]', $form->value()->foo);

        $view = $form->view();

        $this->assertEquals('["[\"a\",true]",false]', $view['foo']->value());
    }

    public function test_code_generator()
    {
        $form = new class() extends AttributeForm {
            public StringElement $foo;

            #[AsTransformer('foo')]
            public function fooTransformer($value, StringElement $input, bool $toPhp)
            {
                return json_encode([$value, $toPhp]);
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->transformer($context->fooTransformer(...));

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
use Tests\Form\Attribute\Element\TestAsTransformerStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestAsTransformerStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));
        $foo->transformer(TestAsTransformerStruct::fooTransformer(...));

        return null;
    }
}

PHP
        , TestAsTransformerStruct::class);
    }
}

class TestAsTransformerStruct
{
    public ?string $foo;

    #[AsTransformer('foo')]
    public static function fooTransformer($value, StringElement $input, bool $toPhp)
    {
        return json_encode([$value, $toPhp]);
    }
}
