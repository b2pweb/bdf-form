<?php

namespace Tests\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\Value\MyEntity;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Button\Groups;
use Bdf\Form\Attribute\Button\Value;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Tests\Form\Attribute\TestCase;

class GenerateConfiguratorStrategyTest extends TestCase
{
    /**
     * @return void
     */
    public function test_form_attributes()
    {
        $generator = new GenerateConfiguratorStrategy('Generated\GeneratedConfigurator');
        $form = new #[Generates(MyEntity::class)] class extends AttributeForm {
        };

        (new ReflectionProcessor($generator))->configureBuilder($form, new FormBuilder());

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Aggregate\Value\MyEntity;
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
        $builder->generates(MyEntity::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
    }
}

PHP
        , $generator->code());
    }

    /**
     * @return void
     */
    public function test_button_properties()
    {
        $generator = new GenerateConfiguratorStrategy('Generated\GeneratedConfigurator');
        $form = new class extends AttributeForm {
            public ButtonInterface $foo;
            #[Value('bar'), Groups('aaa', 'bbb')]
            public ButtonInterface $bar;
        };

        (new ReflectionProcessor($generator))->configureBuilder($form, new FormBuilder());

        $this->assertEquals(<<<'PHP'
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
        $builder->submit('foo')
        ;

        $builder->submit('bar')
            ->value('bar')
            ->groups(['aaa', 'bbb'])
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $root = $form->root();
        $form->foo = $root->button('foo');
        $form->bar = $root->button('bar');
    }
}

PHP
        , $generator->code());
    }

    /**
     * @return void
     */
    public function test_element_properties()
    {
        $generator = new GenerateConfiguratorStrategy('Generated\GeneratedConfigurator');
        $form = new class extends AttributeForm {
            public StringElement $foo;
            #[NotBlank, Positive]
            public IntegerElement $bar;
        };

        (new ReflectionProcessor($generator))->configureBuilder($form, new FormBuilder());

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);

        $bar = $builder->add('bar', IntegerElement::class);
        $bar->satisfy(new NotBlank());
        $bar->satisfy(new Positive());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
        $form->bar = $inner['bar']->element();
    }
}

PHP
        , $generator->code());
    }

    /**
     * @return void
     */
    public function test_generate_post_configure_method_with_private_visiblity()
    {
        $generator = new GenerateConfiguratorStrategy('Generated\GeneratedConfigurator');

        (new ReflectionProcessor($generator))->configureBuilder(new CForm(), new FormBuilder());

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;
use Tests\Form\Attribute\Processor\AForm;
use Tests\Form\Attribute\Processor\BForm;
use Tests\Form\Attribute\Processor\CForm;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $d = $builder->add('d', StringElement::class);

        $builder->submit('b')
        ;

        $c = $builder->add('c', StringElement::class);

        $a = $builder->add('a', StringElement::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $root = $form->root();
        (\Closure::bind(function () use ($inner, $form, $root) {
            $form->d = $inner['d']->element();
        }, null, CForm::class))();
        (\Closure::bind(function () use ($inner, $form, $root) {
            $form->c = $inner['c']->element();
            $form->b = $root->button('b');
        }, null, BForm::class))();
        (\Closure::bind(function () use ($inner, $form, $root) {
            $form->a = $inner['a']->element();
        }, null, AForm::class))();
    }
}

PHP
        , $generator->code());
    }
}

class AForm extends AttributeForm
{
    private StringElement $a;
}

class BForm extends AForm
{
    private ButtonInterface $b;
    private StringElement $c;
}

class CForm extends BForm
{
    private StringElement $d;
}
