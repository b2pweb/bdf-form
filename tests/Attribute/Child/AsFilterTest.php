<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\AsFilter;
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

class AsFilterTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Getter, Setter]
            public StringElement $a;

            #[AsFilter('a')]
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
        $form = new StructForm(TestAsFilterStruct::class, processor: $processor);

        $form->submit(['foo' => 'Zm9v']);
        $this->assertEquals('foo', $form->value()->foo);
    }

    /**
     *
     */
    public function test_code_generator()
    {
        $form = new class() extends AttributeForm {
            #[Getter, Setter]
            public StringElement $foo;
            public StringElement $bar;

            #[AsFilter('foo', 'bar')]
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
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());
        $foo->filter($context->aFilter(...));

        $bar = $builder->add('bar', StringElement::class);
        $bar->filter($context->aFilter(...));

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
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Child\TestAsFilterStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestAsFilterStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));
        $foo->required(null);
        $foo->filter(TestAsFilterStruct::aFilter(...));

        $bar = $builder->add('bar', StringElement::class);
        $bar->hydrator(new Setter(null))->extractor(new Getter(null));
        $bar->required(null);
        $bar->filter(TestAsFilterStruct::aFilter(...));

        return null;
    }
}

PHP
        , TestAsFilterStruct::class
);
    }
}

class TestAsFilterStruct
{
    public string $foo;
    public string $bar;

    #[AsFilter('foo', 'bar')]
    public static function aFilter($value, Child $input, $default)
    {
        return base64_decode($value);
    }
}
