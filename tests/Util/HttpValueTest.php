<?php

namespace Bdf\Form\Util;

use PHPUnit\Framework\TestCase;

class HttpValueTest extends TestCase
{
    /**
     *
     */
    public function test_isEmpty()
    {
        $this->assertTrue(HttpValue::isEmpty(''));
        $this->assertTrue(HttpValue::isEmpty([]));
        $this->assertTrue(HttpValue::isEmpty(null));

        $this->assertFalse(HttpValue::isEmpty('foo'));
        $this->assertFalse(HttpValue::isEmpty('0'));
        $this->assertFalse(HttpValue::isEmpty('0.0'));
        $this->assertFalse(HttpValue::isEmpty(0));
        $this->assertFalse(HttpValue::isEmpty(0.0));
        $this->assertFalse(HttpValue::isEmpty(false));
    }

    /**
     * @dataProvider emptyValues
     */
    public function test_orDefault_with_empty_value($value)
    {
        $this->assertSame($value, HttpValue::orDefault($value, null));
        $this->assertSame('foo', HttpValue::orDefault($value, 'foo'));
    }

    /**
     * @dataProvider notEmptyValues
     */
    public function test_orDefault_with_not_empty_value($value)
    {
        $this->assertSame($value, HttpValue::orDefault($value, null));
        $this->assertSame($value, HttpValue::orDefault($value, 'foo'));
    }

    /**
     * @return array
     */
    public function emptyValues()
    {
        return [
            [null],
            [[]],
            [''],
        ];
    }

    /**
     * @return array
     */
    public function notEmptyValues()
    {
        return [
            ['0'],
            [false],
            [0],
            [0.0],
            ['hello world !'],
            [['foo' => 'hello world !']],
        ];
    }
}
