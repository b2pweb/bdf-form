<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\Required;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\FloatElement;
use Tests\Form\Attribute\TestCase;

class RequiredTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Required]
            public FloatElement $foo;
            #[Required('my message')]
            public FloatElement $bar;
        };

        $form->submit(['foo' => '']);
        $this->assertEquals([
            'foo' => 'This value should not be blank.',
            'bar' => 'my message',
        ], $form->error()->toArray());

        $form->submit(['foo' => '1.2', 'bar' => '4.5']);
        $this->assertTrue($form->valid());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Required]
            public FloatElement $foo;
            #[Required('my message')]
            public FloatElement $bar;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\FloatElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', FloatElement::class);
        $foo->required(null);

        $bar = $builder->add('bar', FloatElement::class);
        $bar->required('my message');

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
