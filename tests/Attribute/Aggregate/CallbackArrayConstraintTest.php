<?php

namespace Tests\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Attribute\Aggregate\CallbackArrayConstraint;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class CallbackArrayConstraintTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[CallbackArrayConstraint('validateFoo', message: 'Foo size must be a multiple of 2')]
            public ArrayElement $foo;

            #[CallbackArrayConstraint('validateFoo')]
            public ArrayElement $bar;

            public function validateFoo(array $value): bool
            {
                return count($value) % 2 === 0;
            }
        };

        $form->submit(['foo' => ['a']]);

        $this->assertFalse($form->valid());
        $this->assertEquals('Foo size must be a multiple of 2', $form->foo->error()->global());

        $form->submit(['foo' => ['a', 'b']]);

        $this->assertTrue($form->valid());
        $this->assertNull($form->foo->error()->global());

        $form->submit(['bar' => ['a']]);

        $this->assertFalse($form->valid());
        $this->assertEquals('The value is invalid', $form->bar->error()->global());

        $form->submit(['bar' => ['a', 'b']]);

        $this->assertTrue($form->valid());
        $this->assertNull($form->bar->error()->global());
    }
    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestCallbackArrayConstraintStruct::class, processor: $processor);

        $form->submit(['foo' => ['a']]);

        $this->assertFalse($form->valid());
        $this->assertEquals('Foo size must be a multiple of 2', $form['foo']->element()->error()->global());

        $form->submit(['foo' => ['a', 'b']]);

        $this->assertTrue($form->valid());
        $this->assertNull($form['foo']->element()->error()->global());

        $form->submit(['bar' => ['a']]);

        $this->assertFalse($form->valid());
        $this->assertEquals('The value is invalid', $form['bar']->element()->error()->global());

        $form->submit(['bar' => ['a', 'b']]);

        $this->assertTrue($form->valid());
        $this->assertNull($form['bar']->element()->error()->global());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[CallbackArrayConstraint('validateFoo', message: 'Foo size must be a multiple of 2')]
            public ArrayElement $foo;

            #[CallbackArrayConstraint('validateFoo')]
            public ArrayElement $bar;

            public function validateFoo(array $value): bool
            {
                return count($value) % 2 === 0;
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Constraint\Closure as ClosureConstraint;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', ArrayElement::class);
        $foo->arrayConstraint(new ClosureConstraint($context->validateFoo(...), 'Foo size must be a multiple of 2'));

        $bar = $builder->add('bar', ArrayElement::class);
        $bar->arrayConstraint(new ClosureConstraint($context->validateFoo(...)));

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

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Constraint\Closure as ClosureConstraint;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Aggregate\TestCallbackArrayConstraintStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestCallbackArrayConstraintStruct::class);

        $foo = $builder->add('foo', ArrayElement::class);
        $foo->arrayConstraint(new ClosureConstraint(\Tests\Form\Attribute\Aggregate\TestCallbackArrayConstraintStruct::validateFoo(...), 'Foo size must be a multiple of 2'));
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));

        $bar = $builder->add('bar', ArrayElement::class);
        $bar->arrayConstraint(new ClosureConstraint(\Tests\Form\Attribute\Aggregate\TestCallbackArrayConstraintStruct::validateFoo(...)));
        $bar->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
            , TestCallbackArrayConstraintStruct::class
        );
    }
}

class TestCallbackArrayConstraintStruct
{
    #[CallbackArrayConstraint('validateFoo', message: 'Foo size must be a multiple of 2')]
    public array $foo;

    #[CallbackArrayConstraint('validateFoo')]
    public array $bar;

    public static function validateFoo(array $value): bool
    {
        return count($value) % 2 === 0;
    }
}
