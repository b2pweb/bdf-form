<?php

namespace Tests\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\Count;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class CountTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
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

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestCountStruct::class, processor: $processor);

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
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
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
use Symfony\Component\Validator\Constraints\Count;
use Tests\Form\Attribute\Aggregate\TestCountStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestCountStruct::class);

        $values = $builder->add('values', ArrayElement::class);
        $values->arrayConstraint(new Count(min: 3, max: 5));
        $values->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
            , TestCountStruct::class);
    }
}

class TestCountStruct
{
    #[Count(min: 3, max: 5)]
    public array $values;
}
