<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Choice\ArrayChoice;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * Class AnyElementBuilderTest
 */
class AnyElementBuilderTest extends TestCase
{
    /**
     * @var AnyElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new AnyElementBuilder();
    }

    /**
     *
     */
    public function test_buildElement()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(AnyElement::class, $element);
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new NotEqualTo('hello'))->buildElement();

        $this->assertFalse($element->submit('hello')->valid());
        $this->assertTrue($element->submit('world')->valid());
    }

    /**
     *
     */
    public function test_satisfy_order()
    {
        $this->builder->satisfy(function () { return 'error 1'; });
        $this->builder->satisfy(function () { return 'error 2'; });
        $element = $this->builder->buildElement();

        $this->assertFalse($element->submit(null)->valid());
        $this->assertEquals('error 1', $element->error()->global());

        $this->builder->satisfy(function () { return 'error 3'; }, null, false);
        $element = $this->builder->buildElement();

        $this->assertFalse($element->submit(null)->valid());
        $this->assertEquals('error 3', $element->error()->global());
    }

    /**
     *
     */
    public function test_satisfy_with_className_and_options()
    {
        $element = $this->builder->satisfy(NotEqualTo::class, ['value' => 'hello'])->buildElement();

        $this->assertFalse($element->submit('hello')->valid());
        $this->assertTrue($element->submit('world')->valid());
    }

    /**
     *
     */
    public function test_transformer_append()
    {
        $element = $this->builder
            ->transformer(function ($value) { return $value . 'a'; }, true)
            ->transformer(function ($value) { return $value . 'b'; }, true)
            ->buildElement()
        ;

        $this->assertEquals('_ba', $element->submit('_')->value());
    }

    /**
     *
     */
    public function test_transformer_prepend()
    {
        $element = $this->builder
            ->transformer(function ($value) { return $value . 'a'; }, false)
            ->transformer(function ($value) { return $value . 'b'; }, false)
            ->buildElement()
        ;

        $this->assertEquals('_ab', $element->submit('_')->value());
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->builder->value('default')->buildElement();

        $this->assertSame('default', $element->value());
    }

    /**
     *
     */
    public function test_required()
    {
        $element = $this->builder->required()->buildElement();

        $element->submit(null);
        $this->assertEquals('This value should not be blank.', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $element = $this->builder->required('my message')->buildElement();

        $element->submit(null);
        $this->assertEquals('my message', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_constraint()
    {
        $element = $this->builder->required(new Positive())->buildElement();

        $element->submit('-1');
        $this->assertEquals('This value should be positive.', $element->error()->global());
    }

    /**
     *
     */
    public function test_choices()
    {
        $element = $this->builder->choices(['foo', 'bar'])->buildElement();

        $this->assertEquals(new ArrayChoice(['foo', 'bar']), $element->choices());

        $element->submit('aaa');
        $this->assertFalse($element->valid());
        $this->assertEquals('The value you selected is not a valid choice.', $element->error()->global());
    }

    /**
     *
     */
    public function test_choices_custom_message()
    {
        $element = $this->builder->choices(['foo', 'bar'], 'my error')->buildElement();

        $element->submit('aaa');
        $this->assertFalse($element->valid());
        $this->assertEquals('my error', $element->error()->global());
    }
}
