<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Choice\ChoiceView;
use Bdf\Form\Constraint\Closure;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class AnyElementTest
 */
class AnyElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new AnyElement();

        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertNull($element->value());
        $this->assertNull($element->httpValue());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new AnyElement();

        $this->assertTrue($element->submit(['hello' => 'world'])->valid());
        $this->assertSame(['hello' => 'world'], $element->value());
        $this->assertSame(['hello' => 'world'], $element->httpValue());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new AnyElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertFalse($element->failed());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new AnyElement(new ConstraintValueValidator([new Length(['max' => 2])]));

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
        $element = new AnyElement(null, $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception_ignored()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new AnyElement(
            new ConstraintValueValidator([], new TransformerExceptionConstraint(['ignoreException' => true])),
            $transformer
        );

        $this->assertTrue($element->submit('aa')->valid());
        $this->assertFalse($element->failed());
        $this->assertSame('aa', $element->value());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception_ignored_should_validate_other_constraints()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new AnyElement(
            new ConstraintValueValidator(
                [new Closure(function () { return 'validation error'; })],
                new TransformerExceptionConstraint(['ignoreException' => true])
            ),
            $transformer
        );

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('validation error', $element->error()->global());
    }

    /**
     *
     */
    public function test_patch_null()
    {
        $element = new AnyElement();
        $element->import('foo');

        $this->assertSame($element, $element->patch(null));
        $this->assertSame('foo', $element->value());
        $this->assertTrue($element->valid());
        $this->assertNull($element->error()->global());
    }

    /**
     *
     */
    public function test_patch_null_with_constraints_should_be_validated()
    {
        $element = (new AnyElementBuilder())->satisfy(new Length(['min' => 5]))->buildElement();
        $element->import('foo');

        $this->assertSame($element, $element->patch(null));
        $this->assertSame('foo', $element->value());
        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('This value is too short. It should have 5 characters or more.', $element->error()->global());
    }

    /**
     *
     */
    public function test_patch_with_value()
    {
        $element = (new AnyElementBuilder())->satisfy(new Length(['min' => 3]))->buildElement();

        $this->assertFalse($element->patch('f')->valid());
        $this->assertSame('f', $element->value());

        $this->assertTrue($element->patch('foo')->valid());
        $this->assertSame('foo', $element->value());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new AnyElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
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
        $element = new AnyElement();

        $this->assertSame('hello', $element->import('hello')->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new AnyElement();

        $this->assertSame('hello', $element->import('hello')->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new AnyElement();

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
        $element = new AnyElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new AnyElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent($form = new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }

    /**
     *
     */
    public function test_view()
    {
        $element = new AnyElement();
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
    public function test_view_with_error()
    {
        $element = (new AnyElementBuilder())->satisfy(function () { return 'error'; })->required()->buildElement();
        $element->submit('f');

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="f" required />', (string) $view);
        $this->assertEquals('my error', $view->onError('my error'));

        $this->assertEquals('f', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertTrue($view->hasError());
        $this->assertEquals('error', $view->error());
    }

    /**
     *
     */
    public function test_view_without_name()
    {
        $element = new AnyElement();

        $this->assertEquals('<input type="text" name="" value="" />', (string) $element->view());
    }

    /**
     *
     */
    public function test_view_with_choice()
    {
        $element = (new AnyElementBuilder())->choices(['Foo' => 'foo', 'Bar' => 'bar'])->required()->buildElement();
        $element->submit('foo');

        $view = $element->view(HttpFieldPath::named('val'));

        $this->assertContainsOnly(ChoiceView::class, $view->choices());
        $this->assertCount(2, $view->choices());

        $this->assertSame('foo', $view->choices()[0]->value());
        $this->assertTrue($view->choices()[0]->selected());
        $this->assertSame('bar', $view->choices()[1]->value());
        $this->assertFalse($view->choices()[1]->selected());

        $this->assertEquals(
            '<select foo="bar" name="val" required><option value="foo" selected>Foo</option><option value="bar">Bar</option></select>'
            , (string) $view->foo('bar')
        );
    }

    /**
     *
     */
    public function test_view_with_choice_and_transformer()
    {
        $element = (new AnyElementBuilder())
            ->choices(['Foo' => 'foo', 'Bar' => 'bar'])
            ->transformer(function ($value, $input, $toPhp) { return $toPhp ? base64_decode($value) : base64_encode($value); })
            ->buildElement()
        ;

        $element->submit('Zm9v');

        $view = $element->view(HttpFieldPath::named('val'));

        $this->assertContainsOnly(ChoiceView::class, $view->choices());
        $this->assertCount(2, $view->choices());

        $this->assertSame('Zm9v', $view->choices()[0]->value());
        $this->assertTrue($view->choices()[0]->selected());
        $this->assertSame('YmFy', $view->choices()[1]->value());
        $this->assertFalse($view->choices()[1]->selected());

        $this->assertEquals(
            '<select foo="bar" name="val"><option value="Zm9v" selected>Foo</option><option value="YmFy">Bar</option></select>'
            , (string) $view->foo('bar')
        );
    }

    /**
     *
     */
    public function test_error()
    {
        $element = (new AnyElementBuilder())->satisfy(function() { return false; })->buildElement();
        $element->submit('ok');

        $error = $element->error(HttpFieldPath::named('foo'));

        $this->assertEquals('foo', $error->field());
        $this->assertEquals('The value is invalid', $error->global());
        $this->assertEquals('CUSTOM_ERROR', $error->code());
        $this->assertEmpty($error->children());
    }
}
