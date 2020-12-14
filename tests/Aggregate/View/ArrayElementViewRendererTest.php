<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Aggregate\ArrayElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Class ArrayElementViewRendererTest
 */
class ArrayElementViewRendererTest extends TestCase
{

    /**
     *
     */
    public function test_render_csv()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', 'aaa,bbb,ccc', null, [], true, []);
        $renderer = new ArrayElementViewRenderer();

        $this->assertEquals('<input class="my-custom-style" type="text" name="foo" value="aaa,bbb,ccc" required />', $renderer->render($view, ['class' => 'my-custom-style']));
        $this->assertEquals('<input type="email" multiple name="foo" value="aaa,bbb,ccc" required />', $renderer->render($view, ['type' => 'email', 'multiple' => true]));
    }

    /**
     * @dataProvider provideConstraints
     */
    public function test_render_csv_constraints($constraints, $attributes)
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', 'aaa,bbb,ccc', null, [], false, $constraints);
        $renderer = new ArrayElementViewRenderer();

        $this->assertEquals('<input type="text" name="foo" value="aaa,bbb,ccc"'.$attributes.' />', $renderer->render($view, []));
    }

    public function provideConstraints()
    {
        return [
            [[], ''],
            [[Length::class => ['min' => null, 'max' => 35]], ' maxlength="35"'],
            [[Length::class => ['min' => 3, 'max' => 35]], ' minlength="3" maxlength="35"'],
            [[Length::class => ['min' => 3], LessThanOrEqual::class => ['value' => 42]], ' minlength="3" max="42"'],
            [[GreaterThanOrEqual::class => ['value' => 42]], ' min="42"'],
            [[PositiveOrZero::class => ['value' => 0]], ' min="0"'],
        ];
    }

    /**
     *
     */
    public function test_render_should_ignore_value_and_name_attributes()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', 'aaa,bbb,ccc', null, [], false, []);
        $renderer = new ArrayElementViewRenderer();

        $this->assertEquals('<input name="foo" value="aaa,bbb,ccc" type="text" />', $renderer->render($view, ['name' => 'other', 'value' => 'other']));
    }

    /**
     *
     */
    public function test_render_xss()
    {
        $view = new ArrayElementView(ArrayElement::class, 'foo', '<b>hello</b>', null, [], false, []);
        $renderer = new ArrayElementViewRenderer();

        $this->assertEquals('<input test="&lt;b&gt;test&lt;/b&gt;" other="&quot;&gt;other-test" type="text" name="foo" value="&lt;b&gt;hello&lt;/b&gt;" />', $renderer->render($view, ['test' => '<b>test</b>', 'other' => '">other-test']));
    }

    /**
     *
     */
    public function test_instance()
    {
        $this->assertInstanceOf(ArrayElementViewRenderer::class, ArrayElementViewRenderer::instance());
        $this->assertSame(ArrayElementViewRenderer::instance(), ArrayElementViewRenderer::instance());
    }
}
