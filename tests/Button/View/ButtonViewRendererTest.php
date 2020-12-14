<?php

namespace Bdf\Form\Button\View;

use PHPUnit\Framework\TestCase;

/**
 * Class ButtonViewRendererTest
 */
class ButtonViewRendererTest extends TestCase
{
    /**
     *
     */
    public function test_render_input()
    {
        $view = new ButtonView('foo', 'bar', true);
        $renderer = new ButtonViewRenderer();

        $this->assertEquals('<input class="my-custom-style" type="submit" name="foo" value="bar" />', $renderer->render($view, ['class' => 'my-custom-style']));
        $this->assertEquals('<input type="reset" name="foo" value="bar" />', $renderer->render($view, ['type' => 'reset']));
    }

    /**
     *
     */
    public function test_render_button()
    {
        $view = new ButtonView('foo', 'bar', true);
        $renderer = new ButtonViewRenderer();

        $this->assertEquals('<button class="my-custom-style" type="submit" name="foo" value="bar"><b>Hello World !</b></button>', $renderer->render($view, ['class' => 'my-custom-style', 'inner' => '<b>Hello World !</b>']));
    }

    /**
     *
     */
    public function test_render_should_ignore_value_and_name_attributes()
    {
        $view = new ButtonView('foo', 'bar', true);
        $renderer = new ButtonViewRenderer();

        $this->assertEquals('<input name="foo" value="bar" type="submit" />', $renderer->render($view, ['name' => 'other', 'value' => 'other']));
    }

    /**
     *
     */
    public function test_render_xss()
    {
        $view = new ButtonView('foo', '<b>hello</b>', true);
        $renderer = new ButtonViewRenderer();

        $this->assertEquals('<input test="&lt;b&gt;test&lt;/b&gt;" other="&quot;&gt;other-test" type="submit" name="foo" value="&lt;b&gt;hello&lt;/b&gt;" />', $renderer->render($view, ['test' => '<b>test</b>', 'other' => '">other-test']));
    }

    /**
     *
     */
    public function test_instance()
    {
        $this->assertInstanceOf(ButtonViewRenderer::class, ButtonViewRenderer::instance());
        $this->assertSame(ButtonViewRenderer::instance(), ButtonViewRenderer::instance());
    }
}
