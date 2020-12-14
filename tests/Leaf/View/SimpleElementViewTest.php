<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\Leaf\StringElement;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Class SimpleElementViewTest
 */
class SimpleElementViewTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', 'my error', true, [Length::class => ['min' => 5]]);

        $this->assertSame(StringElement::class, $view->type());
        $this->assertSame('foo', $view->name());
        $this->assertSame('bar', $view->value());
        $this->assertSame('my error', $view->error());
        $this->assertTrue($view->hasError());
        $this->assertTrue($view->required());
        $this->assertEquals([Length::class => ['min' => 5]], $view->constraints());
        $this->assertSame([], $view->attributes());
    }

    /**
     *
     */
    public function test_serialization_should_ignore_attributes()
    {
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', 'my error', true, [Length::class => ['min' => 5]]);

        $view->foo('bar')->myAttr('attr value');

        $view = unserialize(serialize($view));

        $this->assertSame(StringElement::class, $view->type());
        $this->assertSame('foo', $view->name());
        $this->assertSame('bar', $view->value());
        $this->assertSame('my error', $view->error());
        $this->assertTrue($view->hasError());
        $this->assertTrue($view->required());
        $this->assertEquals([Length::class => ['min' => 5]], $view->constraints());
        $this->assertSame([], $view->attributes());
    }

    /**
     *
     */
    public function test_onError_with_error()
    {
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', 'my error', true, [Length::class => ['min' => 5]]);

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
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, true, [Length::class => ['min' => 5]]);
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
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, true, [Length::class => ['min' => 5]]);

        $this->assertSame([], $view->attributes());
        $this->assertSame(['foo' => 'bar'], $view->set('foo', 'bar')->attributes());
        $this->assertSame([], $view->unset('foo')->attributes());
        $this->assertSame(['foo' => 'bar', 'rab' => 'oof'], $view->foo('bar')->rab('oof')->attributes());
    }

    /**
     *
     */
    public function test_set_invalid_type()
    {
        $this->expectException(\TypeError::class);

        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, true, [Length::class => ['min' => 5]]);
        $view->set('foo', new \stdClass());
    }

    /**
     *
     */
    public function test_magic_call_missing_argument_invalid_type()
    {
        $this->expectException(\ArgumentCountError::class);

        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, true, [Length::class => ['min' => 5]]);
        $view->foo();
    }

    /**
     *
     */
    public function test_render()
    {
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, true, [Length::class => ['min' => 5]]);
        $view->foo('bar');

        $this->assertEquals('<input foo="bar" type="text" name="foo" value="bar" required minlength="5" />', $view->render());
        $this->assertEquals('<input foo="bar" type="text" name="foo" value="bar" required minlength="5" />', (string) $view);
        $this->assertEquals('<custom-element />', $view->render(new class implements FieldViewRendererInterface {
            public function render(FieldViewInterface $view, array $attributes): string
            {
                return '<custom-element />';
            }
        }));
    }
}
