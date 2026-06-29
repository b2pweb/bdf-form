<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\ElementType;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\GetSet;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class GetSetTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[GetSet]
            public StringElement $a;

            #[GetSet('c')]
            public StringElement $b;
        };

        $form->submit(['a' => 'z', 'b' => 'e']);
        $this->assertSame(['a' => 'z', 'c' => 'e'], $form->value());

        $form->import(['a' => 'aaa', 'c' => 'ccc']);
        $this->assertSame('aaa', $form->a->value());
        $this->assertSame('ccc', $form->b->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestGetSetStruct::class, processor: $processor);

        $form->submit(['a' => 'z', 'b' => 'e']);
        $this->assertEquals(new TestGetSetStruct(a: 'z', b: 'e'), $form->value());

        $form->import(new TestGetSetStruct(a: 'aaa', b: 'ccc'));
        $this->assertSame('aaa', $form['a']->element()->value());
        $this->assertSame('ccc', $form['b']->element()->value());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[GetSet('c')]
            public StringElement $b;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $b = $builder->add('b', StringElement::class);
        $b->hydrator(new Setter('c'))->extractor(new Getter('c'));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->b = $inner['b']->element();
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

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Child\TestGetSetStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestGetSetStruct::class);

        $a = $builder->add('a', StringElement::class);
        $a->hydrator(new Setter(null))->extractor(new Getter(null));
        $a->required(null);

        $b = $builder->add('b', StringElement::class);
        $b->hydrator(new Setter('b'))->extractor(new Getter('b'));
        $b->required(null);

        return null;
    }
}

PHP
            , TestGetSetStruct::class);
    }
}

class TestGetSetStruct
{
    public function __construct(
        #[GetSet]
        public string $a,

        #[GetSet('b')]
        public string $b,
    ) {}
}
