<?php

namespace Bdf\Form\Error;

use PHPUnit\Framework\TestCase;

/**
 * Class FormErrorTest
 */
class FormErrorTest extends TestCase
{
    /**
     *
     */
    public function test_null()
    {
        $this->assertInstanceOf(FormError::class, FormError::null());
        $this->assertTrue(FormError::null()->empty());
        $this->assertSame(FormError::null(), FormError::null());
    }

    /**
     *
     */
    public function test_message()
    {
        $error = FormError::message('my error');

        $this->assertInstanceOf(FormError::class, $error);
        $this->assertFalse($error->empty());
        $this->assertEquals('my error', $error->global());
        $this->assertEmpty($error->children());
    }

    /**
     *
     */
    public function test_aggregate()
    {
        $error = FormError::aggregate($errors = ['child' => FormError::message('child error')]);

        $this->assertInstanceOf(FormError::class, $error);
        $this->assertFalse($error->empty());
        $this->assertNull($error->global());
        $this->assertSame($errors, $error->children());
    }

    /**
     *
     */
    public function test_toArray_global_message()
    {
        $error = new FormError('my error', []);

        $this->assertEquals(['my error'], $error->toArray());
    }

    /**
     *
     */
    public function test_toArray_aggregate()
    {
        $error = FormError::aggregate([
            'child' => new FormError('child error', ['inner' => FormError::message('inner error')]),
            'embedded' => FormError::aggregate([
                'el1' => FormError::message('el1 error'),
                'el2' => FormError::message('el2 error'),
            ])
        ]);

        $this->assertEquals([
            'child' => 'child error',
            'embedded' => [
                'el1' => 'el1 error',
                'el2' => 'el2 error',
            ]
        ], $error->toArray());
    }

    /**
     *
     */
    public function test_toArray_mixed()
    {
        $error = new FormError('global error', [
            'child' => FormError::message('child error'),
        ]);

        $this->assertEquals([
            'global error',
            'child' => 'child error',
        ], $error->toArray());
    }

    /**
     *
     */
    public function test_print_mixed()
    {
        $error = new FormError('global error', [
            'child' => FormError::message('child error'),
            'child2' => FormError::message('child2 error'),
        ]);

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->at(0))->method('global')->with('global error');
        $printer->expects($this->at(1))->method('child')->with('child', FormError::message('child error'));
        $printer->expects($this->at(2))->method('child')->with('child2', FormError::message('child2 error'));
        $printer->expects($this->at(3))->method('print')->willReturn('formatted');

        $this->assertEquals('formatted', $error->print($printer));
    }

    /**
     *
     */
    public function test_print_global()
    {
        $error = FormError::message('global error');

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->at(0))->method('global')->with('global error');
        $printer->expects($this->at(1))->method('print')->willReturn('formatted');
        $printer->expects($this->never())->method('child');

        $this->assertEquals('formatted', $error->print($printer));
    }

    /**
     *
     */
    public function test_print_aggregate()
    {
        $error = FormError::aggregate([
            'child' => FormError::message('child error'),
            'child2' => FormError::message('child2 error'),
        ]);

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->never())->method('global');
        $printer->expects($this->at(0))->method('child')->with('child', FormError::message('child error'));
        $printer->expects($this->at(1))->method('child')->with('child2', FormError::message('child2 error'));
        $printer->expects($this->at(2))->method('print')->willReturn('formatted');

        $this->assertEquals('formatted', $error->print($printer));
    }

    /**
     *
     */
    public function test_print_null()
    {
        $error = FormError::null();

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->never())->method('global');
        $printer->expects($this->never())->method('child');
        $printer->expects($this->once())->method('print')->willReturn('formatted');

        $this->assertEquals('formatted', $error->print($printer));
    }

    /**
     *
     */
    public function test_toString()
    {
        $this->assertEquals('global error', FormError::message('global error'));
        $this->assertEquals("child : child error\nchild2 : child2 error", FormError::aggregate([
            'child' => FormError::message('child error'),
            'child2' => FormError::message('child2 error'),
        ]));
    }
}
