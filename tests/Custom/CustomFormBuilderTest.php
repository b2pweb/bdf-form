<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Leaf\IntegerElement;
use PHPUnit\Framework\TestCase;

/**
 * Class CustomFormBuilderTest
 */
class CustomFormBuilderTest extends TestCase
{
    /**
     *
     */
    public function test_with_class_name()
    {
        $builder = new CustomFormBuilder(MyCustomTestForm::class);

        $form = $builder->buildElement();
        $this->assertInstanceOf(MyCustomTestForm::class, $form);
        $this->assertArrayHasKey('foo', $form);
        $this->assertArrayNotHasKey('other', $form);
    }

    /**
     *
     */
    public function test_with_factory_function()
    {
        $builder = new CustomFormBuilder(function (ElementBuilderInterface $builder) {
            return new MyCustomTestForm($builder, true);
        });

        $form = $builder->buildElement();
        $this->assertInstanceOf(MyCustomTestForm::class, $form);
        $this->assertArrayHasKey('foo', $form);
        $this->assertArrayHasKey('other', $form);
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $builder = new CustomFormBuilder(MyCustomTestForm::class);

        $form = $builder->satisfy(function ($value, $form) {
            if ($form['foo']->element()->value() == 'bar') {
                return 'error';
            }
        })->buildElement();

        $this->assertEquals('error', $form->submit(['foo' => 'bar'])->error()->global());
        $this->assertTrue($form->submit(['foo' => 'baz'])->valid());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $builder = new CustomFormBuilder(MyCustomTestForm::class);

        $form = $builder->transformer(function ($value) {
            return array_flip($value);
        })->buildElement();

        $this->assertEquals(['foo' => 'bar'], $form->submit(['bar' => 'foo'])->value());
    }

    /**
     *
     */
    public function test_value()
    {
        $builder = new CustomFormBuilder(MyCustomTestForm::class);

        $form = $builder->value(['foo' => 'bar'])->buildElement();

        $this->assertEquals(['foo' => 'bar'], $form->value());
    }

    /**
     *
     */
    public function test_forward_call()
    {
        $builder = new CustomFormBuilder(MyCustomTestForm::class);

        $form = $builder->integer('value')->buildElement();

        $this->assertArrayHasKey('value', $form);
        $this->assertInstanceOf(IntegerElement::class, $form['value']->element());
    }
}

class MyCustomTestForm extends CustomForm
{
    /**
     * @var bool
     */
    private $otherField;

    public function __construct(?ElementBuilderInterface $builder = null, bool $otherField = false)
    {
        parent::__construct($builder);

        $this->otherField = $otherField;
    }

    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->string('foo')->setter()->getter();

        if ($this->otherField) {
            $builder->string('other');
        }
    }
}
