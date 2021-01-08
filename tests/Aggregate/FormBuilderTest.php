<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Button\SubmitButtonBuilder;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Csrf\CsrfElement;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Phone\PhoneElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraints\Count;
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
    public function test_float()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->float('value'));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(FloatElement::class, $form['value']->element());
    }

    /**
     *
     */
    public function test_boolean()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->boolean('value'));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(BooleanElement::class, $form['value']->element());
    }

    /**
     *
     */
    public function test_dateTime()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->dateTime('value'));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(DateTimeElement::class, $form['value']->element());
    }

    /**
     *
     */
    public function test_phone()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->phone('value'));

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(PhoneElement::class, $form['value']->element());
    }

    /**
     *
     */
    public function test_csrf()
    {
        $this->assertInstanceOf(ChildBuilder::class, $this->builder->csrf());

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertInstanceOf(CsrfElement::class, $form['_token']->element());
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

    /**
     *
     */
    public function test_required()
    {
        $element = $this->builder->required()->buildElement();

        $element->submit([]);
        $this->assertEquals('This value should not be blank.', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $element = $this->builder->required('my message')->buildElement();

        $element->submit([]);
        $this->assertEquals('my message', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_constraint()
    {
        $element = $this->builder->required(new Count(['min' => 2]))->buildElement();

        $element->submit([]);
        $this->assertEquals('This collection should contain 2 elements or more.', $element->error()->global());
    }
}

class GenerateTestEntity
{
    public $foo;
}
