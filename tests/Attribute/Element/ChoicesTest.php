<?php

namespace Tests\Form\Attribute\Element;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\Choices;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Choice\ArrayChoice;
use Bdf\Form\Leaf\StringElement;
use Tests\Form\Attribute\TestCase;

class ChoicesTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Choices(choices: ['foo', 'bar'], message: 'my error')]
            public StringElement $foo;

            #[Choices(choices: ['foo', 'bar', 'baz'], message: 'my error', options: ['min' => 2])]
            public ArrayElement $bar;

            #[Choices('generateChoices')]
            public StringElement $baz;

            public function generateChoices()
            {
                return ['aaa', 'bbb', 'ccc'];
            }
        };

        $form->submit(['foo' => 'a', 'bar' => ['b'], 'baz' => 'c']);

        $this->assertEquals(new ArrayChoice(['foo', 'bar']), $form->foo->choices());
        $this->assertEquals(new ArrayChoice(['foo', 'bar', 'baz']), $form->bar->choices());
        $this->assertEquals(['aaa', 'bbb', 'ccc'], $form->baz->choices()->values());
        $this->assertEquals(['foo' => 'my error', 'bar' => 'my error', 'baz' => 'The value you selected is not a valid choice.'], $form->error()->toArray());

        $form->submit(['foo' => 'bar', 'bar' => ['bar', 'foo'], 'baz' => 'ccc']);
        $this->assertTrue($form->valid());

        $form->submit(['foo' => 'bar', 'bar' => ['bar'], 'baz' => 'ccc']);
        $this->assertEquals(['bar' => 'You must select at least 2 choices.'], $form->error()->toArray());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Choices(choices: ['foo', 'bar'], message: 'my error')]
            public StringElement $foo;

            #[Choices(choices: ['foo', 'bar', 'baz'], message: 'my error', options: ['min' => 2])]
            public ArrayElement $bar;

            #[Choices('generateChoices')]
            public StringElement $baz;

            public function generateChoices()
            {
                return ['aaa', 'bbb', 'ccc'];
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Choice\LazyChoice;
use Bdf\Form\Leaf\StringElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);
        $foo->choices(['foo', 'bar'], ['multipleMessage' => 'my error', 'message' => 'my error']);

        $bar = $builder->add('bar', ArrayElement::class);
        $bar->choices(['foo', 'bar', 'baz'], ['min' => 2, 'multipleMessage' => 'my error', 'message' => 'my error']);

        $baz = $builder->add('baz', StringElement::class);
        $baz->choices(new LazyChoice([$form, 'generateChoices']), []);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
        $form->bar = $inner['bar']->element();
        $form->baz = $inner['baz']->element();
    }
}

PHP
        , $form
);
    }
}
