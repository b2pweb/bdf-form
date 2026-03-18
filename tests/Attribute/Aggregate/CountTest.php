<?php

namespace Tests\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\Count;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Tests\Form\Attribute\TestCase;

class CountTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Count(min: 3, max: 5)]
            public ArrayElement $values;
        };

        $form->submit([]);
        $this->assertFalse($form->valid());
        $this->assertEquals(['values' => 'This collection should contain 3 elements or more.'], $form->error()->toArray());

        $form->submit(['values' => ['aaa', 'bbb', 'ccc', 'ddd', 'eee', 'fff']]);
        $this->assertFalse($form->valid());
        $this->assertEquals(['values' => 'This collection should contain 5 elements or less.'], $form->error()->toArray());

        $form->submit(['values' => ['aaa', 'bbb', 'ccc']]);
        $this->assertTrue($form->valid());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Count(min: 3, max: 5)]
            public ArrayElement $values;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Symfony\Component\Validator\Constraints\Count;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $values = $builder->add('values', ArrayElement::class);
        $values->arrayConstraint(new Count(min: 3, max: 5));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->values = $inner['values']->element();
    }
}

PHP
            , $form);
    }
}
