<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\Raw;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\FloatElement;
use Tests\Form\Attribute\TestCase;

class RawTest extends TestCase
{
    private $lastLocale;

    protected function setUp(): void
    {
        $this->lastLocale = \Locale::getDefault();
        \Locale::setDefault('FR_fr');
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->lastLocale);
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Raw]
            public FloatElement $foo;
            #[Raw(false)]
            public FloatElement $bar;
        };

        $form->submit(['foo' => '1,23', 'bar' => '1,23']);

        $this->assertSame(1.0, $form->foo->value());
        $this->assertSame(1.23, $form->bar->value());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Raw]
            public FloatElement $foo;
            #[Raw(false)]
            public FloatElement $bar;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\FloatElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', FloatElement::class);
        $foo->raw(true);

        $bar = $builder->add('bar', FloatElement::class);
        $bar->raw(false);

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
            , $form
        );
    }
}
