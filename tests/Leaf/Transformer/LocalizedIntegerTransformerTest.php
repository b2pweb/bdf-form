<?php

namespace Bdf\Form\Leaf\Transformer;

use Bdf\Form\ElementInterface;
use Locale;
use PHPUnit\Framework\TestCase;

class LocalizedIntegerTransformerTest extends TestCase
{
    /**
     * @dataProvider provideFromHttpRoundingMode
     */
    public function test_fromHttp_scale_and_roundingMode($mode, $httpValue, $phpValue)
    {
        $transformer = new LocalizedIntegerTransformer(false, $mode);

        $this->assertSame($phpValue, $transformer->transformFromHttp($httpValue, $this->createMock(ElementInterface::class)));
    }

    /**
     *
     */
    public function provideFromHttpRoundingMode()
    {
        return [
            [\NumberFormatter::ROUND_CEILING, '12.3456789', 13],
            [\NumberFormatter::ROUND_FLOOR, '12.3456789', 12],
            [\NumberFormatter::ROUND_DOWN, '12.3456789', 12],
            [\NumberFormatter::ROUND_UP, '12.3456789', 13],
            [\NumberFormatter::ROUND_HALFDOWN, '12.3456789', 12],
            [\NumberFormatter::ROUND_HALFUP, '12.3456789', 12],
            [\NumberFormatter::ROUND_HALFEVEN, '12.3456789', 12],
        ];
    }

    /**
     * @dataProvider provideToHttpRoundingMode
     */
    public function test_toHttp_roundingMode($mode, $phpValue, $httpValue)
    {
        $transformer = new LocalizedIntegerTransformer(false, $mode);

        $this->assertSame($httpValue, $transformer->transformToHttp($phpValue, $this->createMock(ElementInterface::class)));
    }

    /**
     *
     */
    public function provideToHttpRoundingMode()
    {
        return [
            [\NumberFormatter::ROUND_CEILING, 12.3456789, '13'],
            [\NumberFormatter::ROUND_FLOOR, 12.3456789, '12'],
            [\NumberFormatter::ROUND_DOWN, 12.3456789, '12'],
            [\NumberFormatter::ROUND_UP, 12.3456789, '13'],
            [\NumberFormatter::ROUND_HALFDOWN, 12.3456789, '12'],
            [\NumberFormatter::ROUND_HALFUP, 12.3456789, '12'],
            [\NumberFormatter::ROUND_HALFEVEN, 12.3456789, '12'],
        ];
    }

    /**
     *
     */
    public function test_fromHttp_should_handle_standard_english_notation()
    {
        $transformer = new LocalizedIntegerTransformer(false, \NumberFormatter::ROUND_HALFEVEN, 'fr');

        $this->assertSame(12, $transformer->transformFromHttp('12.34', $this->createMock(ElementInterface::class)));
    }

    /**
     *
     */
    public function test_locale()
    {
        $transformer = new LocalizedIntegerTransformer(false, \NumberFormatter::ROUND_HALFEVEN, 'fr');
        $element = $this->createMock(ElementInterface::class);

        $this->assertSame(12, $transformer->transformFromHttp('12,34', $element));
        $this->assertSame('12', $transformer->transformToHttp(12.34, $element));

        $transformer = new LocalizedIntegerTransformer(false, \NumberFormatter::ROUND_HALFEVEN, 'ar');
        $this->assertSame(12, $transformer->transformFromHttp('١٢٫٣٤', $element));
        $this->assertSame('١٢', $transformer->transformToHttp(12.34, $element));
    }

    /**
     *
     */
    public function test_grouping()
    {
        $transformer = new LocalizedIntegerTransformer(true);
        $element = $this->createMock(ElementInterface::class);

        $this->assertSame(1235, $transformer->transformFromHttp('1,234.56', $element));
        $this->assertSame('1,235', $transformer->transformToHttp(1234.56, $element));

        $transformer = new LocalizedIntegerTransformer();

        $this->assertSame(1235, $transformer->transformFromHttp('1234.56', $element));
        $this->assertSame('1235', $transformer->transformToHttp(1234.56, $element));
    }

    /**
     *
     */
    public function test_integer()
    {
        $transformer = new LocalizedIntegerTransformer();
        $element = $this->createMock(ElementInterface::class);

        $this->assertSame(1234, $transformer->transformFromHttp('1234', $element));
        $this->assertSame('1234', $transformer->transformToHttp(1234, $element));
    }

    /**
     *
     */
    public function test_empty()
    {
        $transformer = new LocalizedIntegerTransformer();
        $element = $this->createMock(ElementInterface::class);

        $this->assertNull($transformer->transformFromHttp('', $element));
        $this->assertNull($transformer->transformFromHttp(null, $element));
        $this->assertNull($transformer->transformToHttp(null, $element));
    }

    /**
     * @dataProvider invalidValue
     */
    public function test_transformFromHttp_invalid_number($value, $error)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($error);

        $transformer = new LocalizedIntegerTransformer();
        $element = $this->createMock(ElementInterface::class);

        $transformer->transformFromHttp($value, $element);
    }

    public function invalidValue()
    {
        return [
            ['foo', 'Number parsing failed: U_PARSE_ERROR'],
            ['1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'Number parsing failed: U_INVALID_FORMAT_ERROR'],
            [[], 'Expected a scalar or null.'],
            [false, 'Expected a scalar or null.'],
            [new \stdClass(), 'Expected a scalar or null.'],
        ];
    }

    /**
     * @dataProvider invalidPhpValue
     */
    public function test_transformToHttp_not_a_number($value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a numeric or null.');

        $transformer = new LocalizedIntegerTransformer();
        $element = $this->createMock(ElementInterface::class);

        $transformer->transformToHttp($value, $element);
    }

    public function invalidPhpValue()
    {
        return [
            ['foo'],
            [[]],
            [false],
            [new \stdClass()],
        ];
    }
}
