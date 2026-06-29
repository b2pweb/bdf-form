<?php

namespace Tests\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\ArrayConstraint;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraints\Unique;
use Tests\Form\Attribute\TestCase;

class ArrayConstraintTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[ArrayConstraint(new Unique(message: 'Not unique'))]
            public ArrayElement $values;
        };

        $form->submit(['values' => ['aaa', 'aaa']]);
        $this->assertFalse($form->valid());
        $this->assertEquals(['values' => 'Not unique'], $form->error()->toArray());

        $form->submit(['values' => ['aaa', 'bbb']]);
        $this->assertTrue($form->valid());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestArrayConstraintStruct::class, processor: $processor);

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
            #[ArrayConstraint(new Unique(message: 'Not unique'))]
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
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $values = $builder->add('values', ArrayElement::class);
        $values->arrayConstraint(new Unique(message: 'Not unique', groups: ['Default']));

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

    /**
     * @return void
     */
    public function test_code_generator_struct()
    {
        $this->assertGeneratedStruct(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Symfony\Component\Validator\Constraints\Unique;
use Tests\Form\Attribute\Aggregate\TestArrayConstraintStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestArrayConstraintStruct::class);

        $values = $builder->add('values', ArrayElement::class);
        $values->arrayConstraint(new Unique(message: 'Not unique', groups: ['Default']));
        $values->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
            , TestArrayConstraintStruct::class);
    }
}

class TestArrayConstraintStruct
{
    #[ArrayConstraint(new Unique(message: 'Not unique'))]
    public array $values;
}
