<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Class ArrayElementBuilderTest
 */
class ArrayElementBuilderTest extends TestCase
{
    /**
     * @var ArrayElementBuilder
     */
    private $builder;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->builder = new ArrayElementBuilder();
    }

    /**
     *
     */
    public function test_defaults()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(ArrayElement::class, $element);
        $this->assertEmpty($element->value());

        $element->import(['foo']);
        $this->assertInstanceOf(StringElement::class, $element[0]->element());
    }

    /**
     *
     */
    public function test_string_with_configurator()
    {
        $element = $this->builder->string(function (StringElementBuilder $builder) {
            $builder->transformer(function ($s) { return strtolower($s); });
        })->buildElement();

        $this->assertEquals(['foo', 'bar'], $element->submit(['FOO', 'BAR'])->value());
    }

    /**
     *
     */
    public function test_integer()
    {
        $element = $this->builder->integer()->buildElement();

        $this->assertSame([5, 6], $element->submit(['5', '6'])->value());
    }

    /**
     *
     */
    public function test_integer_with_configurator()
    {
        $element = $this->builder->integer(function (IntegerElementBuilder $builder) {
            $builder
                ->raw()
                ->transformer(function ($v) { return hexdec($v); })
            ;
        })->buildElement();

        $this->assertSame([10, 12], $element->submit(['a', 'c'])->value());
    }

    /**
     *
     */
    public function test_form()
    {
        $element = $this->builder->form(function (FormBuilder $builder) {
            $builder->string('firstName')->setter();
            $builder->string('lastName')->setter();
        })->buildElement();

        $element->submit([
            ['firstName' => 'Mickey', 'lastName' => 'Mouse'],
            ['firstName' => 'Minnie', 'lastName' => 'Mouse'],
        ]);

        $this->assertSame([
            ['firstName' => 'Mickey', 'lastName' => 'Mouse'],
            ['firstName' => 'Minnie', 'lastName' => 'Mouse'],
        ], $element->value());
    }

    /**
     *
     */
    public function test_arrayConstraint()
    {
        $element = $this->builder->arrayConstraint(new NotBlank())->buildElement();

        $this->assertFalse($element->submit([])->valid());
        $this->assertEquals('This value should not be blank.', $element->submit([])->error()->global());
    }

    /**
     *
     */
    public function test_arrayTransformer()
    {
        $element = $this->builder->arrayTransformer(function ($value) { return array_flip($value); })->buildElement();
        $this->assertSame(['bar' => 'foo'], $element->submit(['foo' => 'bar'])->value());
    }

    /**
     *
     */
    public function test_count()
    {
        $element = $this->builder->count(['min' => 3])->buildElement();

        $this->assertFalse($element->submit(['foo', 'bar'])->valid());
        $this->assertTrue($element->submit(['foo', 'bar', 'baz'])->valid());
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new NotEqualTo('foo'))->buildElement();

        $this->assertFalse($element->submit(['foo', 'bar'])->valid());
        $this->assertEquals([0 => 'This value should not be equal to "foo".'], $element->error()->toArray());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = $this->builder->transformer(function ($v) { return strtoupper($v); })->buildElement();

        $this->assertSame(['FOO', 'BAR'], $element->submit(['foo', 'bar'])->value());
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->builder->value(['foo', 'bar'])->buildElement();

        $this->assertSame(['foo', 'bar'], $element->value());
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
