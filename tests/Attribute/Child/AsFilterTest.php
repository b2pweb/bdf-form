<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\AsFilter;
use Bdf\Form\Attribute\Child\CallbackFilter;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Child\Child;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\TestCase;

class AsFilterTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Getter, Setter]
            public StringElement $a;

            #[AsFilter('a')]
            public function aFilter($value, Child $input, $default)
            {
                return base64_decode($value);
            }
        };

        $form->submit(['a' => 'Zm9v']);
        $this->assertEquals('foo', $form->a->value());
    }

    /**
     *
     */
    public function test_code_generator()
    {
        $form = new class() extends AttributeForm {
            #[Getter, Setter]
            public StringElement $foo;
            public StringElement $bar;

            #[AsFilter('foo', 'bar')]
            public function aFilter($value, Child $input, $default)
            {
                return base64_decode($value);
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
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());
        $foo->filter([$form, 'aFilter']);

        $bar = $builder->add('bar', StringElement::class);
        $bar->filter([$form, 'aFilter']);

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
