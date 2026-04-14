<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Element\IgnoreTransformerException;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class IgnoreTransformerExceptionTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[IgnoreTransformerException, CallbackTransformer('transform')]
            public StringElement $foo;

            #[IgnoreTransformerException(false), CallbackTransformer('transform')]
            public StringElement $bar;

            public function transform()
            {
                throw new \Exception('My error');
            }
        };

        $form->submit(['foo' => 'a', 'bar' => 'b']);

        $this->assertFalse($form->valid());
        $this->assertEquals(['bar' => 'My error'], $form->error()->toArray());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestIgnoreTransformerExceptionStruct::class, processor: $processor);

        $form->submit(['foo' => 'a', 'bar' => 'b']);

        $this->assertFalse($form->valid());
        $this->assertEquals(['bar' => 'My error'], $form->error()->toArray());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[IgnoreTransformerException, CallbackTransformer('transform')]
            public StringElement $foo;

            #[IgnoreTransformerException(false), CallbackTransformer('transform')]
            public StringElement $bar;

            public function transform()
            {
                throw new \Exception('My error');
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
        $foo->ignoreTransformerException(true);
        $foo->transformer($context->transform(...));

        $bar = $builder->add('bar', StringElement::class);
        $bar->ignoreTransformerException(false);
        $bar->transformer($context->transform(...));

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
use Tests\Form\Attribute\Element\TestIgnoreTransformerExceptionStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestIgnoreTransformerExceptionStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->ignoreTransformerException(true);
        $foo->transformer(TestIgnoreTransformerExceptionStruct::transform(...));
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));

        $bar = $builder->add('bar', StringElement::class);
        $bar->ignoreTransformerException(false);
        $bar->transformer(TestIgnoreTransformerExceptionStruct::transform(...));
        $bar->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
        , TestIgnoreTransformerExceptionStruct::class
);
    }
}

class TestIgnoreTransformerExceptionStruct
{
    #[IgnoreTransformerException, CallbackTransformer('transform')]
    public ?string $foo;

    #[IgnoreTransformerException(false), CallbackTransformer('transform')]
    public ?string $bar;

    public static function transform()
    {
        throw new \Exception('My error');
    }
}
