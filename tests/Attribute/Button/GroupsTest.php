<?php

namespace Tests\Form\Attribute\Button;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Button\Groups;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Button\ButtonInterface;
use Tests\Form\Attribute\TestCase;

class GroupsTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Groups('foo', 'bar')]
            public ButtonInterface $btn;
        };

        $form->submit([]);
        $this->assertEquals(['Default'], $form->root()->constraintGroups());

        $form->submit(['btn' => 'ok']);
        $this->assertEquals(['foo', 'bar'], $form->root()->constraintGroups());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Groups('foo', 'bar')]
            public ButtonInterface $btn;
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
        $builder->submit('btn')
            ->groups(['foo', 'bar'])
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $root = $form->root();
        $form->btn = $root->button('btn');
    }
}

PHP
            , $form);
    }
}
