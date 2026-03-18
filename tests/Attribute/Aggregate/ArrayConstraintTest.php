<?php

namespace Tests\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\ArrayConstraint;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Symfony\Component\Validator\Constraints\Unique;
use Tests\Form\Attribute\TestCase;

class ArrayConstraintTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[ArrayConstraint(Unique::class, ['message' => 'Not unique'])]
            public ArrayElement $values;
        };

        $form->submit(['values' => ['aaa', 'aaa']]);
        $this->assertFalse($form->valid());
        $this->assertEquals(['values' => 'Not unique'], $form->error()->toArray());

        $form->submit(['values' => ['aaa', 'bbb']]);
        $this->assertTrue($form->valid());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[ArrayConstraint(Unique::class, ['message' => 'Not unique'])]
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
use Symfony\Component\Validator\Constraints\Unique;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $values = $builder->add('values', ArrayElement::class);
        $values->arrayConstraint(Unique::class, ['message' => 'Not unique']);

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
