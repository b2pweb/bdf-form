<?php

namespace Bdf\Form\Error;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\ValidatorBuilder;

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
        $this->assertNull($error->code());
        $this->assertEmpty($error->children());
    }

    /**
     *
     */
    public function test_message_with_code()
    {
        $error = FormError::message('my error', 'MY_CODE');

        $this->assertInstanceOf(FormError::class, $error);
        $this->assertFalse($error->empty());
        $this->assertEquals('my error', $error->global());
        $this->assertEquals('MY_CODE', $error->code());
        $this->assertEmpty($error->children());
    }


    /**
     *
     */
    public function test_violation()
    {
        $validator = (new ValidatorBuilder)->getValidator();
        $violation = $validator->validate('foo', new Length(['min' => 5]))->get(0);
        $error = FormError::violation($violation);

        $this->assertInstanceOf(FormError::class, $error);
        $this->assertFalse($error->empty());
        $this->assertEquals('This value is too short. It should have 5 characters or more.', $error->global());
        $this->assertEquals('TOO_SHORT_ERROR', $error->code());
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
        $this->assertNull($error->code());
        $this->assertSame($errors, $error->children());
    }

    /**
     *
     */
    public function test_toArray_global_message()
    {
        $error = new FormError('my error', null, []);

        $this->assertEquals(['my error'], $error->toArray());
    }

    /**
     *
     */
    public function test_toArray_aggregate()
    {
        $error = FormError::aggregate([
            'child' => new FormError('child error', null, ['inner' => FormError::message('inner error')]),
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
        $error = new FormError('global error', null, [
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
        $error = new FormError('global error', 'MY_CODE', [
            'child' => FormError::message('child error'),
            'child2' => FormError::message('child2 error'),
        ]);

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->at(0))->method('global')->with('global error');
        $printer->expects($this->at(1))->method('code')->with('MY_CODE');
        $printer->expects($this->at(2))->method('child')->with('child', FormError::message('child error'));
        $printer->expects($this->at(3))->method('child')->with('child2', FormError::message('child2 error'));
        $printer->expects($this->at(4))->method('print')->willReturn('formatted');

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
    public function test_print_global_and_code()
    {
        $error = FormError::message('global error', 'MY_CODE');

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->at(0))->method('global')->with('global error');
        $printer->expects($this->at(1))->method('code')->with('MY_CODE');
        $printer->expects($this->at(2))->method('print')->willReturn('formatted');
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
