<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class StringElementTest
 */
class StringElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new StringElement();

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
        $element = new StringElement();

        $this->assertTrue($element->submit('hello')->valid());
        $this->assertSame('hello', $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new StringElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new StringElement(new ConstraintValueValidator(new Length(['max' => 2])));

        $this->assertFalse($element->submit('hello')->valid());
        $this->assertSame('hello', $element->value());
        $this->assertEquals('This value is too long. It should have 2 characters or less.', $element->error()->global());

        $this->assertTrue($element->submit('he')->valid());
        $this->assertSame('he', $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new StringElement(null, $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new StringElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? $value . '_' : substr($value, 0, -1);
        }));

        $element->submit('hello');
        $this->assertSame('hello_', $element->value());
        $this->assertEquals('hello', $element->httpValue());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new StringElement();

        $this->assertSame('hello', $element->import('hello')->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new StringElement();

        $this->assertSame('hello', $element->import('hello')->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new StringElement();

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
        $element = new StringElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new StringElement();

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
        $element = new StringElement();
        $element->import('foo');

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="foo" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="foo" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('foo', $view->value());
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
        $element = (new StringElementBuilder())->length(['min' => 3, 'max' => 35])->required()->buildElement();
        $element->import('foo');

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="foo" required minlength="3" maxlength="35" />', (string) $view);
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('foo', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertTrue($view->required());
        $this->assertEquals([NotBlank::class => [], Length::class => ['min' => 3, 'max' => 35]], $view->constraints());
    }

    /**
     *
     */
    public function test_view_with_error()
    {
        $element = (new StringElementBuilder())->length(['min' => 3, 'max' => 35])->required()->buildElement();
        $element->submit('f');

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="f" required minlength="3" maxlength="35" />', (string) $view);
        $this->assertEquals('my error', $view->onError('my error'));

        $this->assertEquals('f', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertTrue($view->hasError());
        $this->assertEquals('This value is too short. It should have 3 characters or more.', $view->error());
    }

    /**
     *
     */
    public function test_view_without_name()
    {
        $element = new StringElement();

        $this->assertEquals('<input type="text" name="" value="" />', (string) $element->view());
    }
}
