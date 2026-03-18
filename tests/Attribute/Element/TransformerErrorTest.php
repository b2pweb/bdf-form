<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Element\TransformerError;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use http\Message;
use Tests\Form\Attribute\TestCase;

class TransformerErrorTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[CallbackTransformer('transformer'), TransformerError('my message')]
            public StringElement $foo;
            #[CallbackTransformer('transformer'), TransformerError(message: 'bar', code: 'BAR_ERROR')]
            public StringElement $bar;

            public function transformer()
            {
                throw new \Exception('My error');
            }
        };

        $form->submit(['foo' => 'a', 'bar' => 'b']);

        $this->assertEquals(['foo' => 'my message', 'bar' => 'bar'], $form->error()->toArray());
        $this->assertEquals('BAR_ERROR', $form->error()->children()['bar']->code());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_with_callback(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[CallbackTransformer('transformer'), TransformerError(validationCallback: 'handleError')]
            public StringElement $foo;

            public function transformer()
            {
                throw new \Exception('My error');
            }

            public function handleError($value, TransformerExceptionConstraint $constraint)
            {
                if ($value === 'a') {
                    return false;
                }

                $constraint->message = str_repeat($value, 5);
                $constraint->code = 'FOO';

                return true;
            }
        };

        $form->submit(['foo' => 'a']);
        $this->assertTrue($form->valid());

        $form->submit(['foo' => 'b']);
        $this->assertEquals(['foo' => 'bbbbb'], $form->error()->toArray());
        $this->assertEquals('FOO', $form->error()->children()['foo']->code());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[CallbackTransformer('transformer'), TransformerError('my message')]
            public StringElement $foo;
            #[CallbackTransformer('transformer'), TransformerError(message: 'bar', code: 'BAR_ERROR')]
            public StringElement $bar;

            public function transformer()
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
        $foo->transformer([$form, 'transformer']);
        $foo
            ->transformerErrorMessage('my message')
        ;

        $bar = $builder->add('bar', StringElement::class);
        $bar->transformer([$form, 'transformer']);
        $bar
            ->transformerErrorMessage('bar')
            ->transformerErrorCode('BAR_ERROR')
        ;

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
