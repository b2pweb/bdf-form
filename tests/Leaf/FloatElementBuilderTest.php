<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Choice\ArrayChoice;
use Locale;
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

    private $lastLocale;

    protected function setUp(): void
    {
        $this->builder = new FloatElementBuilder();
        $this->lastLocale = Locale::getDefault();
        Locale::setDefault('fr');
    }

    protected function tearDown(): void
    {
        Locale::setDefault($this->lastLocale);
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
    public function test_transformer_order()
    {
        $this->builder->transformer(function ($value, $element, $toPhp) { return $value .= '1'; });
        $this->builder->transformer(function ($value, $element, $toPhp) { return $value .= '2'; });
        $this->builder->transformer(function ($value, $element, $toPhp) { return $value .= '3'; }, false);

        $element = $this->builder->buildElement();

        $this->assertEquals(213, $element->submit('')->value());
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
    public function test_default_transformer_error()
    {
        $element = $this->builder->buildElement();

        $element->submit('invalid');

        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('The value is not a valid number.', $element->error()->global());
        $this->assertEquals('INVALID_NUMBER_ERROR', $element->error()->code());
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
    public function test_min_with_float_val()
    {
        $element = $this->builder->min(0.1)->buildElement();

        $this->assertFalse($element->submit(0)->valid());
        $this->assertTrue($element->submit(0.2)->valid());
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
    public function test_max_with_float_val()
    {
        $element = $this->builder->max(1.5)->buildElement();

        $this->assertTrue($element->submit(1.4)->valid());
        $this->assertFalse($element->submit(1.6)->valid());
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
    public function test_positive()
    {
        $element = $this->builder->positive()->buildElement();

        $this->assertFalse($element->submit(-0.1)->valid());
        $this->assertTrue($element->submit(0.1)->valid());
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
        $element = $this->builder->scale(1)->roundingMode(\NumberFormatter::ROUND_UP)->buildElement();

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

        $this->assertTrue($element->submit('10,1')->valid());
        $this->assertSame(10.0, $element->value());
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
        $element = $this->builder->choices([12.3, 45.6, 78.9])->buildElement();

        $this->assertEquals(new ArrayChoice([12.3, 45.6, 78.9]), $element->choices());

        $element->submit('14.7');
        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('The value you selected is not a valid choice.', $element->error()->global());

        $element->submit('45.6');
        $this->assertTrue($element->valid());
    }

    /**
     *
     */
    public function test_choices_custom_message()
    {
        $element = $this->builder->choices([12.3, 45.6, 78.9], 'my error')->buildElement();

        $element->submit('14.7');
        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('my error', $element->error()->global());
    }
}
