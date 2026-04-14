<?php

namespace Tests\Form\Attribute\Constraint;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Constraint\AsConstraint;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

use function strlen;

class AsConstraintTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            public StringElement $foo;
            public StringElement $bar;

            #[AsConstraint('foo', message: 'Foo length must be a multiple of 2')]
            #[AsConstraint('bar')]
            public function validateFoo($value): bool
            {
                return strlen((string) $value) % 2 === 0;
            }
        };

        $form->submit(['foo' => 'a']);

        $this->assertFalse($form->valid());
        $this->assertEquals('Foo length must be a multiple of 2', $form->foo->error()->global());

        $form->submit(['foo' => 'abcd']);

        $this->assertTrue($form->valid());
        $this->assertNull($form->foo->error()->global());

        $form->submit(['bar' => 'a']);

        $this->assertFalse($form->valid());
        $this->assertEquals('The value is invalid', $form->bar->error()->global());

        $form->submit(['bar' => 'abcd']);

        $this->assertTrue($form->valid());
        $this->assertNull($form->bar->error()->global());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestAsConstraintStruct::class, processor: $processor);

        $form->submit(['foo' => 'a']);

        $this->assertFalse($form->valid());
        $this->assertEquals('Foo length must be a multiple of 2', $form['foo']->error()->global());

        $form->submit(['foo' => 'abcd']);

        $this->assertTrue($form->valid());
        $this->assertNull($form['foo']->error()->global());

        $form->submit(['bar' => 'a']);

        $this->assertFalse($form->valid());
        $this->assertEquals('The value is invalid', $form['bar']->error()->global());

        $form->submit(['bar' => 'abcd']);

        $this->assertTrue($form->valid());
        $this->assertNull($form['bar']->error()->global());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            public StringElement $foo;

            #[AsConstraint('foo', message: 'Foo length must be a multiple of 2')]
            public function validateFoo($value): bool
            {
                return strlen($value) % 2 === 0;
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Constraint\Closure as ClosureConstraint;
use Bdf\Form\Leaf\StringElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->satisfy(new ClosureConstraint($context->validateFoo(...), 'Foo length must be a multiple of 2'));

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
use Bdf\Form\Constraint\Closure as ClosureConstraint;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Constraint\TestAsConstraintStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestAsConstraintStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));
        $foo->satisfy(new ClosureConstraint(TestAsConstraintStruct::validateFoo(...), 'Foo length must be a multiple of 2'));

        $bar = $builder->add('bar', StringElement::class);
        $bar->hydrator(new Setter(null))->extractor(new Getter(null));
        $bar->satisfy(new ClosureConstraint(TestAsConstraintStruct::validateFoo(...)));

        return null;
    }
}

PHP
        , TestAsConstraintStruct::class
);
    }
}

class TestAsConstraintStruct
{
    public ?string $foo;
    public ?string $bar;

    #[AsConstraint('foo', message: 'Foo length must be a multiple of 2')]
    #[AsConstraint('bar')]
    public static function validateFoo($value): bool
    {
        return strlen((string) $value) % 2 === 0;
    }
}
