<?php

namespace Tests\Form\Attribute\Processor\Element;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Filter\FilterInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Tests\Form\Attribute\TestCase;

class FilterAttributeProcessorTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[AFilter, BFilter]
            public StringElement $foo;
        };

        $form->submit(['foo' => 'bar']);
        $this->assertEquals('barAB', $form->foo->value());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[AFilter, BFilter]
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
use Tests\Form\Attribute\Processor\Element\AFilter;
use Tests\Form\Attribute\Processor\Element\BFilter;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->filter(new AFilter());
        $foo->filter(new BFilter());

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

#[Attribute(Attribute::TARGET_PROPERTY)]
class AFilter implements FilterInterface
{
    public function filter($value, ChildInterface $input, $default)
    {
        return $value . 'A';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class BFilter implements FilterInterface
{
    public function filter($value, ChildInterface $input, $default)
    {
        return $value . 'B';
    }
}
