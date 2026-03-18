<?php

namespace Tests\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Attribute\Processor\ReflectionStrategyInterface;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\Leaf\StringElement;
use Tests\Form\Attribute\TestCase;

class ReflectionProcessorTest extends TestCase
{
    public function test_should_iterate_hierarchy()
    {
        $strategy = $this->createMock(ReflectionStrategyInterface::class);
        $processor = new ReflectionProcessor($strategy);

        $form = new B();
        $builder = new FormBuilder();

        $strategy->expects($this->exactly(2))->method('onFormClass')
            ->withConsecutive(
                [new \ReflectionClass(B::class), $form, $builder],
                [new \ReflectionClass(A::class), $form, $builder],
            )
        ;

        $processor->configureBuilder($form, $builder);
    }

    public function test_should_not_configure_twice_same_element_property()
    {
        $strategy = $this->createMock(ReflectionStrategyInterface::class);
        $processor = new ReflectionProcessor($strategy);

        $form = new B();
        $builder = new FormBuilder();

        $strategy->expects($this->once())->method('onElementProperty')
            ->with(new \ReflectionProperty(B::class, 'foo'), 'foo', StringElement::class, $form, $builder)
        ;

        $processor->configureBuilder($form, $builder);
    }

    public function test_should_not_configure_twice_same_button_property()
    {
        $strategy = $this->createMock(ReflectionStrategyInterface::class);
        $processor = new ReflectionProcessor($strategy);

        $form = new B();
        $builder = new FormBuilder();

        $strategy->expects($this->once())->method('onButtonProperty')
            ->with(new \ReflectionProperty(B::class, 'btn'), 'btn', $form, $builder)
        ;

        $processor->configureBuilder($form, $builder);
    }
}

class A extends AttributeForm
{
    public StringElement $foo;
    protected ButtonInterface $btn;

    public ButtonInterface|StringElement $withUnionType;
    public \ArrayObject $withInvalidType;
}

class B extends A
{
    public $withoutType;
    public array $withNotObjectType;

    public StringElement $foo;
    protected ButtonInterface $btn;
}
