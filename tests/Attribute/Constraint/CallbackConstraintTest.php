<?php

namespace Tests\Form\Attribute\Constraint;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Constraint\CallbackConstraint;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Tests\Form\Attribute\TestCase;

class CallbackConstraintTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[CallbackConstraint('validateFoo', message: 'Foo length must be a multiple of 2')]
            public StringElement $foo;

            #[CallbackConstraint('validateFoo')]
            public StringElement $bar;

            public function validateFoo($value): bool
            {
                return strlen((string) $value) % 2 === 0;
            }
        };

        $form->submit(['foo' => 'a']);

        $this->assertFalse($form->valid());
        $this->assertEquals('Foo length must be a multiple of 2', $form->foo->error()->global());

        $form->submit(['foo' => 'abcd']);

        $this->assertTrue($form->valid());
        $this->assertNull($form->foo->error()->global());

        $form->submit(['bar' => 'a']);

        $this->assertFalse($form->valid());
        $this->assertEquals('The value is invalid', $form->bar->error()->global());

        $form->submit(['bar' => 'abcd']);

        $this->assertTrue($form->valid());
        $this->assertNull($form->bar->error()->global());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[CallbackConstraint('validateFoo', message: 'Foo length must be a multiple of 2')]
            public StringElement $foo;

            public function validateFoo($value): bool
            {
                return strlen($value) % 2 === 0;
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Constraint\Closure as ClosureConstraint;
use Bdf\Form\Leaf\StringElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->satisfy(new ClosureConstraint(['callback' => [$form, 'validateFoo'], 'message' => 'Foo length must be a multiple of 2']));

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
}
