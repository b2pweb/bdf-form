<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\Csrf\CsrfElement;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Phone\PhoneElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Class SimpleFieldHtmlRendererTest
 */
class SimpleFieldHtmlRendererTest extends TestCase
{
    /**
     *
     */
    public function test_render_simple()
    {
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, true, [Length::class => ['min' => 5]]);
        $renderer = new SimpleFieldHtmlRenderer();

        $this->assertEquals('<input class="my-custom-style" type="text" name="foo" value="bar" required minlength="5" />', $renderer->render($view, ['class' => 'my-custom-style']));
        $this->assertEquals('<input type="email" name="foo" value="bar" required minlength="5" />', $renderer->render($view, ['type' => 'email']));
    }

    /**
     * @dataProvider provideType
     */
    public function test_render_type($elementType, $htmlType)
    {
        $view = new SimpleElementView($elementType, 'foo', 'bar', null, false, []);
        $renderer = new SimpleFieldHtmlRenderer();

        $this->assertEquals('<input type="'.$htmlType.'" name="foo" value="bar" />', $renderer->render($view, []));
    }

    public function provideType()
    {
        return [
            [StringElement::class, 'text'],
            [FloatElement::class, 'text'],
            [IntegerElement::class, 'number'],
            [PhoneElement::class, 'tel'],
            [CsrfElement::class, 'hidden'],
        ];
    }

    /**
     * @dataProvider provideConstraints
     */
    public function test_render_constraints($constraints, $attributes)
    {
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, false, $constraints);
        $renderer = new SimpleFieldHtmlRenderer();

        $this->assertEquals('<input type="text" name="foo" value="bar"'.$attributes.' />', $renderer->render($view, []));
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
        $view = new SimpleElementView(StringElement::class, 'foo', 'bar', null, false, []);
        $renderer = new SimpleFieldHtmlRenderer();

        $this->assertEquals('<input name="foo" value="bar" type="text" />', $renderer->render($view, ['name' => 'other', 'checked' => false, 'value' => 'other']));
    }

    /**
     *
     */
    public function test_render_xss()
    {
        $view = new SimpleElementView(StringElement::class, 'foo', '<b>hello</b>', null, false, []);
        $renderer = new SimpleFieldHtmlRenderer();

        $this->assertEquals('<input test="&lt;b&gt;test&lt;/b&gt;" other="&quot;&gt;other-test" type="text" name="foo" value="&lt;b&gt;hello&lt;/b&gt;" />', $renderer->render($view, ['test' => '<b>test</b>', 'other' => '">other-test']));
    }

    /**
     *
     */
    public function test_instance()
    {
        $this->assertInstanceOf(SimpleFieldHtmlRenderer::class, SimpleFieldHtmlRenderer::instance());
        $this->assertSame(SimpleFieldHtmlRenderer::instance(), SimpleFieldHtmlRenderer::instance());
    }
}
