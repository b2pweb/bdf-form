<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Choice\ArrayChoice;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * Class IntegerElementBuilderTest
 */
class IntegerElementBuilderTest extends TestCase
{
    /**
     * @var IntegerElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new IntegerElementBuilder();
    }

    /**
     *
     */
    public function test_buildElement()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(IntegerElement::class, $element);
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new NotEqualTo(5))->buildElement();

        $this->assertFalse($element->submit(5)->valid());
        $this->assertTrue($element->submit(4)->valid());
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
        $element = $this->builder->value(15)->buildElement();

        $this->assertSame(15, $element->value());
    }

    /**
     *
     */
    public function test_min()
    {
        $element = $this->builder->min(15)->buildElement();

        $this->assertFalse($element->submit(14)->valid());
        $this->assertTrue($element->submit(16)->valid());
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
        $this->assertFalse($element->submit(16)->valid());
    }

    /**
     *
     */
    public function test_max_with_message()
    {
        $element = $this->builder->max(15, 'my error')->buildElement();

        $this->assertEquals('my error', $element->submit(16)->error()->global());
    }

    /**
     *
     */
    public function test_grouping()
    {
        $element = $this->builder->grouping()->buildElement();

        $this->assertTrue($element->submit('15 000')->valid());
        $this->assertSame(15000, $element->value());
    }

    /**
     *
     */
    public function test_roundingMode()
    {
        $element = $this->builder->roundingMode(IntegerToLocalizedStringTransformer::ROUND_UP)->buildElement();

        $this->assertTrue($element->submit('10.1')->valid());
        $this->assertSame(11, $element->value());
    }

    /**
     *
     */
    public function test_raw()
    {
        $element = $this->builder->raw()->buildElement();

        $this->assertTrue($element->submit('10.1')->valid());
        $this->assertSame(10, $element->value());
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
        $element = $this->builder->choices([12, 34, 56])->buildElement();

        $this->assertEquals(new ArrayChoice([12, 34, 56]), $element->choices());

        $element->submit(22);
        $this->assertFalse($element->valid());
        $this->assertEquals('The value you selected is not a valid choice.', $element->error()->global());
    }
}
