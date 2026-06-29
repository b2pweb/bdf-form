<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\Configure;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class ConfigureTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Configure('configureFoo')]
            public StringElement $foo;

            /**
             * @param ChildBuilderInterface|StringElementBuilder $builder
             */
            public function configureFoo(ChildBuilderInterface $builder): void
            {
                $builder->length(min: 3);
            }
        };

        $form->submit(['foo' => 'a']);
        $this->assertFalse($form->valid());
        $this->assertEquals(['foo' => 'This value is too short. It should have 3 characters or more.'], $form->error()->toArray());

        $form->submit(['foo' => 'abc']);
        $this->assertTrue($form->valid());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestConfigureOnPropertyStruct::class, processor: $processor);

        $form->submit(['foo' => 'a']);
        $this->assertFalse($form->valid());
        $this->assertEquals(['foo' => 'This value is too short. It should have 3 characters or more.'], $form->error()->toArray());

        $form->submit(['foo' => 'abc']);
        $this->assertTrue($form->valid());
    }

    #[DataProvider('provideAttributesProcessor')]
    public function test_on_method(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            public StringElement $foo;

            /**
             * @param ChildBuilderInterface|StringElementBuilder $builder
             */
            #[Configure('foo')]
            public function configureFoo(ChildBuilderInterface $builder): void
            {
                $builder->length(min: 3);
            }
        };

        $form->submit(['foo' => 'a']);
        $this->assertFalse($form->valid());
        $this->assertEquals(['foo' => 'This value is too short. It should have 3 characters or more.'], $form->error()->toArray());

        $form->submit(['foo' => 'abc']);
        $this->assertTrue($form->valid());
    }

    #[DataProvider('provideStructAttributesProcessor')]
    public function test_on_method_struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestConfigureOnMethodStruct::class, processor: $processor);

        $form->submit(['foo' => 'a']);
        $this->assertFalse($form->valid());
        $this->assertEquals(['foo' => 'This value is too short. It should have 3 characters or more.'], $form->error()->toArray());

        $form->submit(['foo' => 'abc']);
        $this->assertTrue($form->valid());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Configure('configureFoo')]
            public StringElement $foo;

            /**
             * @param ChildBuilderInterface|StringElementBuilder $builder
             */
            public function configureFoo(ChildBuilderInterface $builder): void
            {
                $builder->length(min: 3);
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $context->configureFoo($foo);

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
use Tests\Form\Attribute\Child\TestConfigureOnPropertyStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestConfigureOnPropertyStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        TestConfigureOnPropertyStruct::configureFoo($foo);
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));
        $foo->required(null);

        return null;
    }
}

PHP
        , TestConfigureOnPropertyStruct::class
);
    }

    public function test_code_generator_on_method()
    {
        $form = new class extends AttributeForm {
            public StringElement $foo;

            /**
             * @param ChildBuilderInterface|StringElementBuilder $builder
             */
            #[Configure('foo')]
            public function configureFoo(ChildBuilderInterface $builder): void
            {
                $builder->length(['min' => 3]);
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $context->configureFoo($foo);

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

    public function test_code_generator_on_method_struct()
    {
        $this->assertGeneratedStruct(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Child\TestConfigureOnMethodStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestConfigureOnMethodStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));
        $foo->required(null);
        TestConfigureOnMethodStruct::configureFoo($foo);

        return null;
    }
}

PHP
            , TestConfigureOnMethodStruct::class
        );
    }
}

class TestConfigureOnPropertyStruct
{
    #[Configure('configureFoo')]
    public string $foo;

    /**
     * @param ChildBuilderInterface|StringElementBuilder $builder
     */
    public static function configureFoo(ChildBuilderInterface $builder): void
    {
        $builder->length(min: 3);
    }
}

class TestConfigureOnMethodStruct
{
    public string $foo;

    /**
     * @param ChildBuilderInterface|StringElementBuilder $builder
     */
    #[Configure('foo')]
    public static function configureFoo(ChildBuilderInterface $builder): void
    {
        $builder->length(min: 3);
    }
}
