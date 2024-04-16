<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class BooleanElementViewTest
 */
class BooleanElementViewTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, 'my error');

        $this->assertSame(BooleanElement::class, $view->type());
        $this->assertSame('foo', $view->name());
        $this->assertSame('true', $view->value());
        $this->assertSame('my error', $view->error());
        $this->assertTrue($view->hasError());
        $this->assertFalse($view->required());
        $this->assertEquals([], $view->constraints());
        $this->assertSame([], $view->attributes());

        $this->assertTrue($view->checked());
        $this->assertSame('true', $view->httpValue());
    }

    /**
     *
     */
    public function test_setValue()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, 'my error');

        $this->assertSame('foo', $view->name());
        $this->assertSame('true', $view->value());

        $view->setValue('false');
        $this->assertSame('false', $view->value());
    }

    /**
     *
     */
    public function test_setError()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, null);

        $this->assertSame(BooleanElement::class, $view->type());
        $this->assertSame('foo', $view->name());
        $this->assertSame('true', $view->value());
        $this->assertNull($view->error());
        $this->assertFalse($view->hasError());
        $this->assertFalse($view->required());
        $this->assertEquals([], $view->constraints());
        $this->assertSame([], $view->attributes());
        $this->assertTrue($view->checked());
        $this->assertSame('true', $view->httpValue());

        $view->setError('my error');
        $this->assertEquals('my error', $view->error());
        $this->assertTrue($view->hasError());
    }

    /**
     *
     */
    public function test_serialization_should_ignore_attributes()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, 'my error');

        $view->foo('bar')->myAttr('attr value');

        $view = unserialize(serialize($view));

        $this->assertSame(BooleanElement::class, $view->type());
        $this->assertSame('foo', $view->name());
        $this->assertSame('true', $view->value());
        $this->assertSame('my error', $view->error());
        $this->assertTrue($view->hasError());
        $this->assertFalse($view->required());
        $this->assertEquals([], $view->constraints());
        $this->assertSame([], $view->attributes());
        $this->assertTrue($view->checked());
        $this->assertSame('true', $view->httpValue());
    }

    /**
     *
     */
    public function test_onError_with_error()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, 'my error');

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
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, null);
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
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, null);

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
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, null);

        $this->assertSame(['foo' => 'bar', 'baz' => true], $view->with(['foo' => 'bar', 'baz'])->attributes());
    }

    /**
     *
     */
    public function test_render()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, null);
        $view->foo('bar');

        $this->assertEquals('<input foo="bar" type="checkbox" name="foo" value="true" checked />', $view->render());
        $this->assertEquals('<input foo="bar" type="checkbox" name="foo" value="true" checked />', (string) $view);
        $this->assertEquals('<custom-element />', $view->render(new class implements FieldViewRendererInterface {
            public function render(FieldViewInterface $view, array $attributes): string
            {
                return '<custom-element />';
            }
        }));
    }

    /**
     *
     */
    public function test_render_not_checked()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', null, 'true', false, null);
        $view->foo('bar');

        $this->assertEquals('<input foo="bar" type="checkbox" name="foo" value="true" />', $view->render());
    }
}
