<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Button\SubmitButtonBuilder;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class FormBuilderTest
 */
class FormBuilderTest extends TestCase
{
    /**
     * @var FormBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new FormBuilder();
    }

    /**
     *
     */
    public function test_string()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->string('firstName'));
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->string('lastName'));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(StringElement::class, $form['firstName']->element());
        $this->assertInstanceOf(StringElement::class, $form['lastName']->element());
    }

    /**
     *
     */
    public function test_integer()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->integer('value'));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(IntegerElement::class, $form['value']->element());
    }

    /**
     *
     */
    public function test_array()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->array('value'));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(ArrayElement::class, $form['value']->element());
    }

    /**
     *
     */
    public function test_array_with_type_and_configurator()
    {
        $this->builder
            ->array('value', IntegerElement::class, function (IntegerElementBuilder $builder) { $builder->raw()->transformer(function ($v) { return hexdec($v); }); })
            ->setter()
        ;

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(ArrayElement::class, $form['value']->element());
        $this->assertSame(['value' => [10, 11]], $form->submit(['value' => ['a', 'b']])->value());
    }

    /**
     *
     */
    public function test_submit()
    {
        $this->assertInstanceOf(SubmitButtonBuilder::class, $this->builder->submit('btn'));

        $form = $this->builder->buildElement();
        $root = $form->root();

        $this->assertEquals('btn', $root->submit(['btn' => 'ok'])->submitButton()->name());
    }

    /**
     *
     */
    public function test_validator()
    {
        $validator = $this->createMock(ValidatorInterface::class);

        $this->builder->validator($validator);

        $this->assertSame($validator, $this->builder->buildElement()->root()->getValidator());
    }

    /**
     *
     */
    public function test_propertyAccessor()
    {
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->builder->propertyAccessor($propertyAccessor);

        $this->assertSame($propertyAccessor, $this->builder->buildElement()->root()->getPropertyAccessor());
    }

    /**
     *
     */
    public function test_embedded()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->embedded('inner'));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form['inner']->element());
    }

    /**
     *
     */
    public function test_embedded_with_configurator()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->embedded('inner', function ($builder) {
            $builder->string('foo');
        }));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form['inner']->element());
        $this->assertInstanceOf(StringElement::class, $form['inner']->element()['foo']->element());
    }

    /**
     *
     */
    public function test_generates()
    {
        $this->builder->generates(GenerateTestEntity::class);
        $this->builder->string('foo')->setter();

        $form = $this->builder->buildElement();

        $value = $form->submit(['foo' => 'bar'])->value();

        $this->assertInstanceOf(GenerateTestEntity::class, $value);
        $this->assertEquals('bar', $value->foo);
    }
}

class GenerateTestEntity
{
    public $foo;
}
