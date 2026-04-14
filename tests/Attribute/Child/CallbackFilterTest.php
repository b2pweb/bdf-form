<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\CallbackFilter;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Child\Child;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

use function base64_decode;

class CallbackFilterTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[CallbackFilter('aFilter'), Getter, Setter]
            public StringElement $a;

            public function aFilter($value, Child $input, $default)
            {
                return base64_decode($value);
            }
        };

        $form->submit(['a' => 'Zm9v']);
        $this->assertEquals('foo', $form->a->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestCallbackFilterStruct::class, processor: $processor);

        $form->submit(['a' => 'Zm9v']);
        $this->assertEquals('foo', $form->value()->a);
    }

    /**
     *
     */
    public function test_code_generator()
    {
        $form = new class() extends AttributeForm {
            #[CallbackFilter('aFilter'), Getter, Setter]
            public StringElement $foo;

            public function aFilter($value, Child $input, $default)
            {
                return base64_decode($value);
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
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->filter($context->aFilter(...));
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());

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
use Tests\Form\Attribute\Child\TestCallbackFilterStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestCallbackFilterStruct::class);

        $a = $builder->add('a', StringElement::class);
        $a->filter(TestCallbackFilterStruct::aFilter(...));
        $a->extractor(new Getter());
        $a->hydrator(new Setter());
        $a->required(null);

        return null;
    }
}

PHP
        , TestCallbackFilterStruct::class
);
    }
}

class TestCallbackFilterStruct
{
    #[CallbackFilter('aFilter'), Getter, Setter]
    public string $a;

    public static function aFilter($value, Child $input, $default)
    {
        return base64_decode($value);
    }
}
