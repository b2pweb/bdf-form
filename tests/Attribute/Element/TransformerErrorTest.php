<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Element\TransformerError;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Struct\StructForm;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use http\Message;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class TransformerErrorTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
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

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestTransformerErrorStruct::class, processor: $processor);

        $form->submit(['foo' => 'a', 'bar' => 'b']);

        $this->assertEquals(['foo' => 'my message', 'bar' => 'bar'], $form->error()->toArray());
        $this->assertEquals('BAR_ERROR', $form->error()->children()['bar']->code());
    }

    #[DataProvider('provideAttributesProcessor')]
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

    #[DataProvider('provideStructAttributesProcessor')]
    public function test_with_callback_struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestTransformerErrorCallbackStruct::class, processor: $processor);

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
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->transformer($context->transformer(...));
        $foo
            ->transformerErrorMessage('my message')
        ;

        $bar = $builder->add('bar', StringElement::class);
        $bar->transformer($context->transformer(...));
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
use Tests\Form\Attribute\Element\TestTransformerErrorStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestTransformerErrorStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->transformer(TestTransformerErrorStruct::transformer(...));
        $foo
            ->transformerErrorMessage('my message')
        ;
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));

        $bar = $builder->add('bar', StringElement::class);
        $bar->transformer(TestTransformerErrorStruct::transformer(...));
        $bar
            ->transformerErrorMessage('bar')
            ->transformerErrorCode('BAR_ERROR')
        ;
        $bar->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
        , TestTransformerErrorStruct::class
);
    }
}
class TestTransformerErrorStruct
{
    #[CallbackTransformer('transformer'), TransformerError('my message')]
    public ?string $foo;

    #[CallbackTransformer('transformer'), TransformerError(message: 'bar', code: 'BAR_ERROR')]
    public ?string $bar;

    public static function transformer()
    {
        throw new \Exception('My error');
    }
}

class TestTransformerErrorCallbackStruct
{
    #[CallbackTransformer('transformer'), TransformerError(validationCallback: 'handleError')]
    public ?string $foo;

    public static function transformer()
    {
        throw new \Exception('My error');
    }

    public static function handleError($value, TransformerExceptionConstraint $constraint)
    {
        if ($value === 'a') {
            return false;
        }

        $constraint->message = str_repeat($value, 5);
        $constraint->code = 'FOO';

        return true;
    }
}
