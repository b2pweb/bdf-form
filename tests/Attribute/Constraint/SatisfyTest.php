<?php

namespace Tests\Form\Attribute\Constraint;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Constraint\Satisfy;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;
use Symfony\Component\Validator\Constraints\Length;

class SatisfyTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Satisfy(new Length(min: 3))]
            public StringElement $foo;
        };

        $form->submit(['foo' => 'ab']);
        $this->assertFalse($form->valid());
        $this->assertEquals(['foo' => 'This value is too short. It should have 3 characters or more.'], $form->error()->toArray());

        $form->submit(['foo' => 'abc']);
        $this->assertTrue($form->valid());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestSatisfyStruct::class, processor: $processor);

        $form->submit(['foo' => 'ab']);
        $this->assertFalse($form->valid());
        $this->assertEquals(['foo' => 'This value is too short. It should have 3 characters or more.'], $form->error()->toArray());

        $form->submit(['foo' => 'abc']);
        $this->assertTrue($form->valid());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Satisfy(new Length(min: 3))]
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

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->satisfy(new Length(min: 3, groups: ['Default']));

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
use Symfony\Component\Validator\Constraints\Length;
use Tests\Form\Attribute\Constraint\TestSatisfyStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestSatisfyStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->satisfy(new Length(min: 3, groups: ['Default']));
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
        , TestSatisfyStruct::class
);
    }
}

class TestSatisfyStruct
{
    #[Satisfy(new Length(min: 3))]
    public ?string $foo;
}
