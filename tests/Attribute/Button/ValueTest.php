<?php

namespace Tests\Form\Attribute\Button;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Button\Groups;
use Bdf\Form\Attribute\Button\Value;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Button\ButtonInterface;
use Tests\Form\Attribute\TestCase;

class ValueTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Value('foo')]
            public ButtonInterface $button;
        };

        $this->assertFalse($form->submit(['button' => 'ok'])->button->clicked());
        $this->assertTrue($form->submit(['button' => 'foo'])->button->clicked());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Value('foo')]
            public ButtonInterface $button;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->submit('button')
            ->value('foo')
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $root = $form->root();
        $form->button = $root->button('button');
    }
}

PHP
            , $form);
    }
}
