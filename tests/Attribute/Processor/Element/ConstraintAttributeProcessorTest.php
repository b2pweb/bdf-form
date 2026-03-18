<?php

namespace Tests\Form\Attribute\Processor\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Tests\Form\Attribute\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotEqualTo;

class ConstraintAttributeProcessorTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Length(min: 5), NotEqualTo('azerty')]
            public StringElement $foo;
        };

        $form->submit(['foo' => 'bar']);

        $this->assertFalse($form->valid());
        $this->assertEquals('This value is too short. It should have 5 characters or more.', $form->foo->error()->global());

        $form->submit(['foo' => 'azerty']);

        $this->assertFalse($form->valid());
        $this->assertEquals('This value should not be equal to "azerty".', $form->foo->error()->global());

        $form->submit(['foo' => 'aqwzsx']);
        $this->assertTrue($form->valid());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Length(min: 5), NotEqualTo('azerty')]
            public StringElement $foo;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotEqualTo;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->satisfy(new Length(min: 5));
        $foo->satisfy(new NotEqualTo('azerty'));

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
