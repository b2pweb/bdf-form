<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\Configure;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Tests\Form\Attribute\TestCase;

class ConfigureTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
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
                $builder->length(['min' => 3]);
            }
        };

        $form->submit(['foo' => 'a']);
        $this->assertFalse($form->valid());
        $this->assertEquals(['foo' => 'This value is too short. It should have 3 characters or more.'], $form->error()->toArray());

        $form->submit(['foo' => 'abc']);
        $this->assertTrue($form->valid());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
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
                $builder->length(['min' => 3]);
            }
        };

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
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $form->configureFoo($foo);

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
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $form->configureFoo($foo);

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
}
