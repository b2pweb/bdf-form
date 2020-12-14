<?php

namespace Bdf\Form\Button\View;

use PHPUnit\Framework\TestCase;

class ButtonViewTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $view = new ButtonView('btn', 'ok', true);

        $this->assertSame('btn', $view->name());
        $this->assertSame('ok', $view->value());
        $this->assertSame([], $view->attributes());
        $this->assertTrue($view->clicked());
    }

    /**
     *
     */
    public function test_build_attributes()
    {
        $view = new ButtonView('btn', 'ok', true);

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

        $view = new ButtonView('btn', 'ok', true);
        $view->set('foo', new \stdClass());
    }

    /**
     *
     */
    public function test_magic_call_missing_argument_invalid_type()
    {
        $this->expectException(\ArgumentCountError::class);

        $view = new ButtonView('btn', 'ok', true);
        $view->foo();
    }

    /**
     *
     */
    public function test_render()
    {
        $view = new ButtonView('btn', 'ok', true);
        $view->foo('bar');

        $this->assertEquals('<input foo="bar" type="submit" name="btn" value="ok" />', $view->render());
        $this->assertEquals('<input foo="bar" type="submit" name="btn" value="ok" />', (string) $view);
        $this->assertEquals('<button foo="bar" type="submit" name="btn" value="ok">label</button>', (string) $view->inner('label'));
        $this->assertEquals('<custom-element />', $view->render(new class implements ButtonViewRendererInterface {
            public function render(ButtonViewInterface $view, array $attributes): string
            {
                return '<custom-element />';
            }
        }));
    }
}
