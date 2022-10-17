<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Aggregate\Form;
use Bdf\Form\Button\View\ButtonView;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\View\ElementViewInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class FormViewTest
 */
class FormViewTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $view = new FormView(Form::class, 'my error', $elements = [
            'foo' => $this->createMock(ElementViewInterface::class),
            'bar' => $this->createMock(ElementViewInterface::class),
        ]);

        $this->assertSame(Form::class, $view->type());
        $this->assertTrue($view->hasError());
        $this->assertSame('my error', $view->error());

        $this->assertSame($elements['foo'], $view['foo']);
        $this->assertSame($elements['bar'], $view['bar']);

        $this->assertTrue(isset($view['foo']));
        $this->assertTrue(isset($view['bar']));
        $this->assertFalse(isset($view['other']));

        $this->assertEquals($elements, iterator_to_array($view));
    }

    /**
     *
     */
    public function test_with_buttons()
    {
        $view = new FormView(Form::class, 'my error', $elements = [
            'foo' => $this->createMock(ElementViewInterface::class),
            'bar' => $this->createMock(ElementViewInterface::class),
        ]);

        $view->setButtons($buttons = ['btn1' => new ButtonView('btn1', 'foo', false)]);

        $this->assertSame(Form::class, $view->type());
        $this->assertTrue($view->hasError());
        $this->assertSame('my error', $view->error());

        $this->assertSame($elements['foo'], $view['foo']);
        $this->assertSame($elements['bar'], $view['bar']);
        $this->assertSame($buttons['btn1'], $view['btn1']);

        $this->assertTrue(isset($view['foo']));
        $this->assertTrue(isset($view['bar']));
        $this->assertTrue(isset($view['btn1']));
        $this->assertFalse(isset($view['other']));

        $this->assertEquals($elements, iterator_to_array($view));
        $this->assertSame($buttons, $view->buttons());
        $this->assertSame($buttons['btn1'], $view->button('btn1'));
    }

    /**
     *
     */
    public function test_serialization()
    {
        $view = new FormView(Form::class, 'my error', $elements = [
            'foo' => $this->createMock(ElementViewInterface::class),
            'bar' => $this->createMock(ElementViewInterface::class),
        ]);

        $view = unserialize(serialize($view));

        $this->assertSame(Form::class, $view->type());
        $this->assertTrue($view->hasError());
        $this->assertSame('my error', $view->error());

        $this->assertEquals($elements['foo'], $view['foo']);
        $this->assertEquals($elements['bar'], $view['bar']);

        $this->assertTrue(isset($view['foo']));
        $this->assertTrue(isset($view['bar']));
        $this->assertFalse(isset($view['other']));

        $this->assertEquals($elements, iterator_to_array($view));
    }

    /**
     *
     */
    public function test_hasError_without_error()
    {
        $view = new FormView(Form::class, null, $elements = [
            'foo' => $this->createMock(ElementViewInterface::class),
            'bar' => $this->createMock(ElementViewInterface::class),
        ]);

        $this->assertFalse($view->hasError());
    }

    /**
     *
     */
    public function test_hasError_with_error_on_child_should_return_true()
    {
        $view = new FormView(Form::class, null, [
            'foo' => $child = $this->createMock(ElementViewInterface::class),
        ]);

        $child->expects($this->once())->method('hasError')->willReturn(true);
        $this->assertTrue($view->hasError());
    }

    /**
     *
     */
    public function test_hasError_with_error_on_form_should_return_true()
    {
        $view = new FormView(Form::class, 'my error', [
            'foo' => $child = $this->createMock(ElementViewInterface::class),
        ]);

        $child->expects($this->never())->method('hasError');
        $this->assertTrue($view->hasError());
    }

    /**
     *
     */
    public function test_offsetSet_should_be_disabled()
    {
        $this->expectException(\BadMethodCallException::class);

        $view = new FormView(Form::class, 'my error', $elements = [
            'foo' => $this->createMock(ElementViewInterface::class),
            'bar' => $this->createMock(ElementViewInterface::class),
        ]);

        $view['foo'] = 'xxx';
    }

    /**
     *
     */
    public function test_offsetUnset_should_be_disabled()
    {
        $this->expectException(\BadMethodCallException::class);

        $view = new FormView(Form::class, 'my error', $elements = [
            'foo' => $this->createMock(ElementViewInterface::class),
            'bar' => $this->createMock(ElementViewInterface::class),
        ]);

        unset($view['foo']);
    }

    /**
     *
     */
    public function test_onError_with_error()
    {
        $view = new FormView(Form::class, 'my error', $elements = [
            'foo' => $this->createMock(ElementViewInterface::class),
            'bar' => $this->createMock(ElementViewInterface::class),
        ]);

        $this->assertSame('message', $view->onError('message'));
        $this->assertSame('message', $view->onError(function (...$params) use(&$out) {
            $out = $params;

            return 'message';
        }));

        $this->assertSame([$view], $out);
    }

    /**
     *
     */
    public function test_onError_without_error()
    {
        $view = new FormView(Form::class, null, $elements = [
            'foo' => $this->createMock(ElementViewInterface::class),
            'bar' => $this->createMock(ElementViewInterface::class),
        ]);
        $out = null;

        $this->assertNull($view->onError('message'));
        $this->assertNull($view->onError(function (...$params) use(&$out) {
            $out = $params;

            return 'message';
        }));

        $this->assertNull($out);
    }

    public function test_value()
    {
        $view = new FormView(Form::class, null, [
            'foo' => new SimpleElementView('', 'foo', 'aaa', null, false, [], null),
            'bar' => new FormView('', null, [
                'a' => new SimpleElementView('', 'bar[a]', 'bbb', null, false, [], null),
                'b' => new SimpleElementView('', 'bar_b', 'ccc', null, false, [], null),
            ]),
        ]);

        $this->assertSame([
            'foo' => 'aaa',
            'bar[a]' => 'bbb',
            'bar_b' => 'ccc',
        ], $view->value());
    }
}
