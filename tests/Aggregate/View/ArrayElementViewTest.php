<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\View\ElementViewInterface;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use PHPUnit\Framework\Constraint\Count;
use PHPUnit\Framework\TestCase;

/**
 * Class ArrayElementViewTest
 */
class ArrayElementViewTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], 'my error', $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $this->assertSame(ArrayElement::class, $view->type());
        $this->assertTrue($view->hasError());
        $this->assertSame('my error', $view->error());
        $this->assertTrue($view->required());
        $this->assertEquals([Count::class => ['max' => 5]], $view->constraints());
        $this->assertFalse($view->isCsv());
        $this->assertEquals(['aaa', 'bbb'], $view->value());

        $this->assertSame($elements[0], $view[0]);
        $this->assertSame($elements[0], $view[0]);

        $this->assertTrue(isset($elements[0]));
        $this->assertTrue(isset($elements[1]));
        $this->assertFalse(isset($elements[42]));

        $this->assertCount(2, $view);
        $this->assertEquals($elements, iterator_to_array($view));
    }

    /**
     *
     */
    public function test_setError()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], null, $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $this->assertSame(ArrayElement::class, $view->type());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertTrue($view->required());
        $this->assertEquals([Count::class => ['max' => 5]], $view->constraints());
        $this->assertFalse($view->isCsv());
        $this->assertEquals(['aaa', 'bbb'], $view->value());

        $view->setError('my error');
        $this->assertTrue($view->hasError());
        $this->assertSame('my error', $view->error());
    }

    /**
     *
     */
    public function test_setValue()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], null, $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $this->assertEquals(['aaa', 'bbb'], $view->value());

        $view->setValue(['ccc', 'ddd']);
        $this->assertEquals(['ccc', 'ddd'], $view->value());
    }

    /**
     *
     */
    public function test_serialization_should_ignore_attributes()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], 'my error', $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $view->foo('bar')->myAttr('attr value');

        $view = unserialize(serialize($view));

        $this->assertSame(ArrayElement::class, $view->type());
        $this->assertTrue($view->hasError());
        $this->assertSame('my error', $view->error());
        $this->assertTrue($view->required());
        $this->assertEquals([Count::class => ['max' => 5]], $view->constraints());
        $this->assertFalse($view->isCsv());
        $this->assertEquals(['aaa', 'bbb'], $view->value());

        $this->assertEquals($elements[0], $view[0]);
        $this->assertEquals($elements[0], $view[0]);

        $this->assertTrue(isset($elements[0]));
        $this->assertTrue(isset($elements[1]));
        $this->assertFalse(isset($elements[42]));

        $this->assertCount(2, $view);
        $this->assertEquals($elements, iterator_to_array($view));
    }

    /**
     *
     */
    public function test_getters_csv()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', 'aaa,bbb', 'my error', $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $this->assertSame(ArrayElement::class, $view->type());
        $this->assertTrue($view->hasError());
        $this->assertSame('my error', $view->error());
        $this->assertTrue($view->required());
        $this->assertEquals([Count::class => ['max' => 5]], $view->constraints());
        $this->assertTrue($view->isCsv());
        $this->assertEquals('aaa,bbb', $view->value());

        $this->assertSame($elements[0], $view[0]);
        $this->assertSame($elements[0], $view[0]);

        $this->assertTrue(isset($elements[0]));
        $this->assertTrue(isset($elements[1]));
        $this->assertFalse(isset($elements[42]));

        $this->assertCount(2, $view);
        $this->assertEquals($elements, iterator_to_array($view));
    }

    /**
     *
     */
    public function test_hasError_without_error()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], null, $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $this->assertFalse($view->hasError());
    }

    /**
     *
     */
    public function test_hasError_with_error_on_child_should_return_true()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], null, $elements = [
            $child = $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $child->expects($this->once())->method('hasError')->willReturn(true);
        $this->assertTrue($view->hasError());
    }

    /**
     *
     */
    public function test_hasError_with_error_on_form_should_return_true()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], 'my error', $elements = [
            $child = $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $child->expects($this->never())->method('hasError');
        $this->assertTrue($view->hasError());
    }

    /**
     *
     */
    public function test_offsetSet_should_be_disabled()
    {
        $this->expectException(\BadMethodCallException::class);

        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], 'my error', $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $view['foo'] = 'xxx';
    }

    /**
     *
     */
    public function test_offsetUnset_should_be_disabled()
    {
        $this->expectException(\BadMethodCallException::class);

        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], 'my error', $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        unset($view['foo']);
    }

    /**
     *
     */
    public function test_onError_with_error()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], 'my error', $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

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
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], null, $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $out = null;

        $this->assertNull($view->onError('message'));
        $this->assertNull($view->onError(function (...$params) use(&$out) {
            $out = $params;

            return 'message';
        }));

        $this->assertNull($out);
    }

    /**
     *
     */
    public function test_build_attributes()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], null, $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $this->assertSame([], $view->attributes());
        $this->assertSame(['foo' => 'bar'], $view->set('foo', 'bar')->attributes());
        $this->assertSame([], $view->unset('foo')->attributes());
        $this->assertSame(['foo' => 'bar', 'rab' => 'oof'], $view->foo('bar')->rab('oof')->attributes());
    }

    /**
     *
     */
    public function test_with()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', ['aaa', 'bbb'], null, $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, [Count::class => ['max' => 5]]);

        $this->assertSame(['foo' => 'bar', 'baz' => true], $view->with(['foo' => 'bar', 'baz'])->attributes());
    }

    /**
     *
     */
    public function test_render_csv()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', 'aaa,bbb', null, $elements = [
            $this->createMock(ElementViewInterface::class),
            $this->createMock(ElementViewInterface::class),
        ], true, []);

        $view->foo('bar');

        $this->assertEquals('<input foo="bar" type="text" name="foo" value="aaa,bbb" required />', $view->render());
        $this->assertEquals('<input foo="bar" type="text" name="foo" value="aaa,bbb" required />', (string) $view);
        $this->assertEquals('<custom-element />', $view->render(new class implements FieldViewRendererInterface {
            public function render(FieldViewInterface $view, array $attributes): string
            {
                return '<custom-element />';
            }
        }));
    }
}
