<?php

namespace Bdf\Form\Leaf;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * Class FloatElementBuilderTest
 */
class FloatElementBuilderTest extends TestCase
{
    /**
     * @var FloatElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new FloatElementBuilder();
    }

    /**
     *
     */
    public function test_buildElement()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(FloatElement::class, $element);
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new NotEqualTo(5))->buildElement();

        $this->assertFalse($element->submit(5)->valid());
        $this->assertTrue($element->submit(5.1)->valid());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = $this->builder->transformer(function ($value, $element, $toPhp) {
            return (string) ($toPhp ? hexdec($value) : dechex($value));
        })->buildElement();

        $this->assertEquals(11, $element->submit('b')->value());
        $this->assertEquals('b', $element->httpValue());
    }

    /**
     *
     */
    public function test_transformer_append()
    {
        $element = $this->builder
            ->transformer(function ($value) { return $value . '0'; }, true)
            ->transformer(function ($value) { return $value . '1'; }, true)
            ->buildElement()
        ;

        $this->assertEquals(110, $element->submit(1)->value());
    }

    /**
     *
     */
    public function test_transformer_prepend()
    {
        $element = $this->builder
            ->transformer(function ($value) { return $value . '0'; }, false)
            ->transformer(function ($value) { return $value . '1'; }, false)
            ->buildElement()
        ;

        $this->assertEquals(101, $element->submit(1)->value());
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->builder->value(15.2)->buildElement();

        $this->assertSame(15.2, $element->value());
    }

    /**
     *
     */
    public function test_min()
    {
        $element = $this->builder->min(15)->buildElement();

        $this->assertFalse($element->submit(14.9)->valid());
        $this->assertTrue($element->submit(15.1)->valid());
    }

    /**
     *
     */
    public function test_min_with_message()
    {
        $element = $this->builder->min(15, 'my error')->buildElement();

        $this->assertEquals('my error', $element->submit(14)->error()->global());
    }

    /**
     *
     */
    public function test_max()
    {
        $element = $this->builder->max(15)->buildElement();

        $this->assertTrue($element->submit(14)->valid());
        $this->assertFalse($element->submit(15.1)->valid());
    }

    /**
     *
     */
    public function test_max_with_message()
    {
        $element = $this->builder->max(15, 'my error')->buildElement();

        $this->assertEquals('my error', $element->submit(15.1)->error()->global());
    }

    /**
     *
     */
    public function test_grouping()
    {
        $element = $this->builder->grouping()->buildElement();

        $this->assertTrue($element->submit('15 000')->valid());
        $this->assertSame(15000.0, $element->value());
    }

    /**
     *
     */
    public function test_roundingMode()
    {
        $element = $this->builder->scale(1)->roundingMode(IntegerToLocalizedStringTransformer::ROUND_UP)->buildElement();

        $this->assertTrue($element->submit('10.11')->valid());
        $this->assertSame(10.2, $element->value());
    }

    /**
     *
     */
    public function test_scale()
    {
        $element = $this->builder->scale(3)->buildElement();

        $this->assertTrue($element->submit('1.23456')->valid());
        $this->assertSame(1.234, $element->value());
    }

    /**
     *
     */
    public function test_raw()
    {
        $element = $this->builder->raw()->buildElement();

        $this->assertTrue($element->submit('10.1')->valid());
        $this->assertSame(10.1, $element->value());
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
}
