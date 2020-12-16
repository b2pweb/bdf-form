<?php

namespace Bdf\Form\Leaf;

use BadMethodCallException;
use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Choice\ChoiceView;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FloatElementTest
 */
class FloatElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new FloatElement();

        $this->assertFalse($element->valid());
        $this->assertNull($element->value());
        $this->assertNull($element->httpValue());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new FloatElement();

        $this->assertTrue($element->submit('5.1')->valid());
        $this->assertSame(5.1, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new FloatElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new FloatElement(new ConstraintValueValidator(new LessThan(2)));

        $this->assertFalse($element->submit('5.1')->valid());
        $this->assertSame(5.1, $element->value());
        $this->assertEquals('This value should be less than 2.', $element->error()->global());

        $this->assertTrue($element->submit('-5.2')->valid());
        $this->assertSame(-5.2, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new FloatElement(null, $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new FloatElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? $value + 2 : $value - 2;
        }));

        $element->submit(1.2)->valid();
        $this->assertSame(3.2, $element->value());
        $this->assertEquals(1.2, $element->httpValue());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new FloatElement();

        $this->assertSame(5.1, $element->import(5.1)->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new FloatElement();

        $this->assertSame('5.1', $element->import(5.1)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new FloatElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $newElement = $element->setContainer($container);

        $this->assertNotSame($element, $newElement);
        $this->assertSame($container, $newElement->container());
    }

    /**
     *
     */
    public function test_root_without_container()
    {
        $element = new FloatElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new FloatElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent(new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }

    /**
     *
     */
    public function test_view()
    {
        $element = new FloatElement();
        $element->import(12.3);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="12.3" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="12.3" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('12.3', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertFalse($view->required());
        $this->assertEmpty($view->constraints());
    }

    /**
     *
     */
    public function test_view_with_constraints()
    {
        $element = (new FloatElementBuilder())->raw()->min(5)->required()->buildElement();
        $element->import(12.3);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="12.3" required min="5" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="12.3" required min="5" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('12.3', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertTrue($view->required());
        $this->assertEquals([NotBlank::class => [], GreaterThanOrEqual::class => ['value' => 5]], $view->constraints());
    }

    /**
     *
     */
    public function test_view_with_error()
    {
        $element = (new FloatElementBuilder())->raw()->min(5)->required()->buildElement();
        $element->submit(3.2);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="3.2" required min="5" />', (string) $view);
        $this->assertEquals('my error', $view->onError('my error'));

        $this->assertEquals('3.2', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertTrue($view->hasError());
        $this->assertEquals('This value should be greater than or equal to 5.', $view->error());
    }

    /**
     *
     */
    public function test_view_without_name()
    {
        $element = (new FloatElementBuilder())->buildElement();

        $this->assertEquals('<input type="text" name="" value="" />', (string) $element->view());
    }

    /**
     *
     */
    public function test_view_with_choice()
    {
        $element = (new FloatElementBuilder())->raw()->choices([1.2, 6.2, 3.2])->required()->buildElement();
        $element->submit(3.2);

        $view = $element->view(HttpFieldPath::named('val'));

        $this->assertContainsOnly(ChoiceView::class, $view->choices());
        $this->assertCount(3, $view->choices());

        $this->assertSame('1.2', $view->choices()[0]->value());
        $this->assertFalse($view->choices()[0]->selected());
        $this->assertSame('6.2', $view->choices()[1]->value());
        $this->assertFalse($view->choices()[1]->selected());
        $this->assertSame('3.2', $view->choices()[2]->value());
        $this->assertTrue($view->choices()[2]->selected());

        $this->assertEquals(
            '<select foo="bar" name="val" required><option value="1.2">1.2</option><option value="6.2">6.2</option><option value="3.2" selected>3.2</option></select>'
            , (string) $view->foo('bar')
        );
    }
}
