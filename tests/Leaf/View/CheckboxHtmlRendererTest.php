<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\Leaf\BooleanElement;
use PHPUnit\Framework\TestCase;

/**
 * Class CheckboxHtmlRendererTest
 */
class CheckboxHtmlRendererTest extends TestCase
{
    /**
     *
     */
    public function test_render_simple()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, null);
        $renderer = new CheckboxHtmlRenderer();

        $this->assertEquals('<input class="my-custom-style" type="checkbox" name="foo" value="true" checked />', $renderer->render($view, ['class' => 'my-custom-style']));
        $this->assertEquals('<input type="radio" name="foo" value="true" checked />', $renderer->render($view, ['type' => 'radio']));

        $view = new BooleanElementView(BooleanElement::class, 'foo', null, 'true', false, null);
        $this->assertEquals('<input type="radio" name="foo" value="true" />', $renderer->render($view, ['type' => 'radio']));
    }

    /**
     *
     */
    public function test_render_should_ignore_value_and_name_attributes()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', 'true', true, null);
        $renderer = new CheckboxHtmlRenderer();

        $this->assertEquals('<input name="foo" checked value="true" type="checkbox" />', $renderer->render($view, ['name' => 'other', 'checked' => false, 'value' => 'other']));
    }

    /**
     *
     */
    public function test_render_xss()
    {
        $view = new BooleanElementView(BooleanElement::class, 'foo', 'true', '<b>hello</b>', true, null);
        $renderer = new CheckboxHtmlRenderer();

        $this->assertEquals('<input test="&lt;b&gt;test&lt;/b&gt;" other="&quot;&gt;other-test" type="checkbox" name="foo" value="&lt;b&gt;hello&lt;/b&gt;" checked />', $renderer->render($view, ['test' => '<b>test</b>', 'other' => '">other-test']));
    }

    /**
     *
     */
    public function test_instance()
    {
       $this->assertInstanceOf(CheckboxHtmlRenderer::class, CheckboxHtmlRenderer::instance());
       $this->assertSame(CheckboxHtmlRenderer::instance(), CheckboxHtmlRenderer::instance());
    }
}
