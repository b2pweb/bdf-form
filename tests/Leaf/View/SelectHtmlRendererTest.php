<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\Choice\ChoiceView;
use Bdf\Form\Leaf\StringElement;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class SelectHtmlRendererTest
 */
class SelectHtmlRendererTest extends TestCase
{
    /**
     *
     */
    public function test_render_simple()
    {
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, true, [], [
            new ChoiceView('foo', 'Foo'),
            new ChoiceView('bar', 'Bar', true),
        ]);
        $renderer = new SelectHtmlRenderer();

        $this->assertEquals(
            '<select class="my-custom-style" name="foo" required><option value="foo">Foo</option><option value="bar" selected>Bar</option></select>',
            $renderer->render($view, ['class' => 'my-custom-style'])
        );
    }

    /**
     *
     */
    public function test_render_without_choice_should_fail()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Choices must be provided for render a select element.');

        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, true, [], []);
        $renderer = new SelectHtmlRenderer();
        $renderer->render($view, ['class' => 'my-custom-style']);
    }

    /**
     *
     */
    public function test_render_xss()
    {
        $view = new SimpleElementView(StringElement::class, 'foo', '<b>hello</b', null, true, [], [
            new ChoiceView('foo', 'Foo'),
            new ChoiceView('<b>hello</b>', '<b>Bar</b>', true),
        ]);
        $renderer = new SelectHtmlRenderer();

        $this->assertEquals(
            '<select test="&lt;b&gt;test&lt;/b&gt;" other="&quot;&gt;other-test" name="foo" required><option value="foo">Foo</option><option value="&lt;b&gt;hello&lt;/b&gt;" selected>&lt;b&gt;Bar&lt;/b&gt;</option></select>',
            $renderer->render($view, ['test' => '<b>test</b>', 'other' => '">other-test'])
        );
    }

    /**
     *
     */
    public function test_instance()
    {
        $this->assertInstanceOf(SelectHtmlRenderer::class, SelectHtmlRenderer::instance());
        $this->assertSame(SelectHtmlRenderer::instance(), SelectHtmlRenderer::instance());
    }
}
