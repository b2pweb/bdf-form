<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Element\IgnoreTransformerException;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Tests\Form\Attribute\TestCase;

class IgnoreTransformerExceptionTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
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
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->ignoreTransformerException(true);
        $foo->transformer([$form, 'transform']);

        $bar = $builder->add('bar', StringElement::class);
        $bar->ignoreTransformerException(false);
        $bar->transformer([$form, 'transform']);

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
