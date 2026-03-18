<?php

namespace Tests\Form\Attribute\Php81\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Attribute\Aggregate\CallbackArrayConstraint;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Tests\Form\Attribute\TestCase;

class CallbackArrayConstraintTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
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
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', ArrayElement::class);
        $foo->arrayConstraint(new ClosureConstraint(['callback' => [$form, 'validateFoo'], 'message' => 'Foo size must be a multiple of 2']));

        $bar = $builder->add('bar', ArrayElement::class);
        $bar->arrayConstraint(new ClosureConstraint([$form, 'validateFoo']));

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
