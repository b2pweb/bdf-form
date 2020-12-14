<?php

namespace Bdf\Form\Leaf;

use BadMethodCallException;
use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class BooleanElementTest
 */
class BooleanElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new BooleanElement();

        $this->assertFalse($element->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new BooleanElement();

        $this->assertTrue($element->submit('1')->valid());
        $this->assertTrue($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new BooleanElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertFalse($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new BooleanElement(new ConstraintValueValidator(new NotBlank()));

        $this->assertFalse($element->submit(null)->valid());
        $this->assertFalse($element->value());
        $this->assertEquals('This value should not be blank.', $element->error()->global());

        $this->assertTrue($element->submit('ok')->valid());
        $this->assertTrue($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new BooleanElement(null, $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new BooleanElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            if ($toPhp) {
                switch ($value) {
                    case '-':
                        return false;
                    case '+':
                        return true;
                }
            } else {
                return $value ? '+' : '-';
            }
        }));

        $element->submit('-')->valid();
        $this->assertFalse($element->value());
        $this->assertSame('-', $element->httpValue());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new BooleanElement();

        $this->assertTrue($element->import(true)->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new BooleanElement();

        $this->assertSame('1', $element->import(true)->httpValue());
        $this->assertNull($element->import(false)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new BooleanElement();

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
        $element = new BooleanElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new BooleanElement();

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
        $element = new BooleanElement();
        $element->import(true);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="checkbox" name="name" value="1" checked />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="checkbox" name="name" value="1" checked />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertTrue($view->checked());
        $this->assertEquals('1', $view->httpValue());
        $this->assertEquals('1', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertFalse($view->required());
        $this->assertEmpty($view->constraints());

        $element->import(false);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="checkbox" name="name" value="1" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="checkbox" name="name" value="1" />', (string) $view->id('foo')->class('form-element'));

        $this->assertFalse($view->checked());
        $this->assertEquals('1', $view->httpValue());
        $this->assertEquals('', $view->value());

        $this->assertEquals('<input type="checkbox" name="" value="1" />', (string) $element->view());
    }
}
