<?php

namespace Bdf\Form\Error;

use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Constraint\Closure;
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
        $this->assertNull(FormError::null()->get('foo'));
        $this->assertSame(FormError::null(), FormError::null()->child('foo'));
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
        $this->assertNull($error->get('foo'));
        $this->assertSame(FormError::null(), $error->child('foo'));
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
    public function test_violation_with_stringable_error()
    {
        $validator = (new ValidatorBuilder)->getValidator();
        $violation = $validator->validate('foo', new Closure(function() {
            return new class {
                public function __toString() { return 'my error'; }
            };
        }))->get(0);
        $error = FormError::violation($violation);

        $this->assertInstanceOf(FormError::class, $error);
        $this->assertFalse($error->empty());
        $this->assertEquals('my error', $error->global());
        $this->assertEquals('CUSTOM_ERROR', $error->code());
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
        $this->assertSame('child error', $error->get('child'));
        $this->assertSame($errors['child'], $error->child('child'));
        $this->assertNull($error->get('not_found'));
        $this->assertSame(FormError::null(), $error->child('not_found'));
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

        $printer->expects($this->once())->method('global')->with('global error');
        $printer->expects($this->once())->method('code')->with('MY_CODE');
        $printer->expects($this->exactly(2))->method('child')->withConsecutive(
            ['child', FormError::message('child error')],
            ['child2', FormError::message('child2 error')]
        );
        $printer->expects($this->once())->method('print')->willReturn('formatted');

        $this->assertEquals('formatted', $error->print($printer));
    }

    /**
     *
     */
    public function test_print_global()
    {
        $error = FormError::message('global error');

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->once())->method('global')->with('global error');
        $printer->expects($this->once())->method('print')->willReturn('formatted');
        $printer->expects($this->never())->method('child');

        $this->assertEquals('formatted', $error->print($printer));
    }

    /**
     *
     */
    public function test_print_field()
    {
        $error = FormError::message('global error')->withField(HttpFieldPath::named('foo'));

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->once())->method('field')->with(HttpFieldPath::named('foo'));
        $printer->expects($this->once())->method('global')->with('global error');
        $printer->expects($this->once())->method('print')->willReturn('formatted');

        $this->assertEquals('formatted', $error->print($printer));
    }

    /**
     *
     */
    public function test_print_global_and_code()
    {
        $error = FormError::message('global error', 'MY_CODE');

        $printer = $this->createMock(FormErrorPrinterInterface::class);

        $printer->expects($this->once())->method('global')->with('global error');
        $printer->expects($this->once())->method('code')->with('MY_CODE');
        $printer->expects($this->once())->method('print')->willReturn('formatted');
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
        $printer->expects($this->exactly(2))->method('child')->withConsecutive(
            ['child', FormError::message('child error')],
            ['child2', FormError::message('child2 error')]
        );
        $printer->expects($this->once())->method('print')->willReturn('formatted');

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

    /**
     *
     */
    public function test_withField()
    {
        $error = FormError::aggregate([
            'a' => FormError::message('a error')->withField(HttpFieldPath::named('a')),
            'b' => FormError::aggregate([
                'c' => FormError::message('c error')->withField(HttpFieldPath::named('c')),
                'd' => FormError::message('d error')->withField(HttpFieldPath::named('d')),
            ])->withField(HttpFieldPath::named('b')),
        ]);

        $newErrors = $error->withField(HttpFieldPath::prefixed('root_'));

        $this->assertNotSame($newErrors, $error);

        $this->assertEquals('root_a', $newErrors->children()['a']->field());
        $this->assertEquals('root_b', $newErrors->children()['b']->field());
        $this->assertEquals('root_b[c]', $newErrors->children()['b']->children()['c']->field());
        $this->assertEquals('root_b[d]', $newErrors->children()['b']->children()['d']->field());
    }
}
