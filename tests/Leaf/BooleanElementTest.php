<?php

namespace Bdf\Form\Leaf;

use BadMethodCallException;
use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Constraint\Closure;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\TransformerExceptionConstraint;
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
        $element = new BooleanElement(new ConstraintValueValidator([new NotBlank()]));

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
    public function test_submit_with_transformer_exception_ignored()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new BooleanElement(
            new ConstraintValueValidator([], new TransformerExceptionConstraint(['ignoreException' => true])),
            $transformer
        );

        $this->assertTrue($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception_ignored_should_validate_other_constraints()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new BooleanElement(
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
     * @dataProvider provideValidValues
     */
    public function test_import($value, $expected)
    {
        $element = new BooleanElement();

        $this->assertSame($expected, $element->import($value)->value());
    }

    public function provideValidValues()
    {
        return [
            ['hello', true],
            [15, true],
            [1.5, true],
            [null, null],
            ['', false],
            [true, true],
            [false, false],
            ['0', false],
        ];
    }

    /**
     * @dataProvider provideInvalidValue
     */
    public function test_import_invalid_type($value)
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('The import()\'ed value of a Bdf\Form\Leaf\BooleanElement must be a scalar value or null');
        $element = new BooleanElement();

        $element->import($value);
    }

    /**
     *
     */
    public function provideInvalidValue()
    {
        return [
            [[]],
            [new \stdClass()],
            [STDIN],
        ];
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
        $container->setParent($form = new Form(new ChildrenCollection()));

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

    /**
     *
     */
    public function test_view_not_submitted()
    {
        $element = new BooleanElement();

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="checkbox" name="name" value="1" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="checkbox" name="name" value="1" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertFalse($view->checked());
        $this->assertEquals('1', $view->httpValue());
        $this->assertNull($view->value());
        $this->assertEquals('name', $view->name());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertFalse($view->required());
        $this->assertEmpty($view->constraints());
    }

    /**
     *
     */
    public function test_error()
    {
        $element = (new BooleanElementBuilder())->satisfy(function() { return false; })->buildElement();
        $element->submit('ok');

        $error = $element->error(HttpFieldPath::named('foo'));

        $this->assertEquals('foo', $error->field());
        $this->assertEquals('The value is invalid', $error->global());
        $this->assertEquals('CUSTOM_ERROR', $error->code());
        $this->assertEmpty($error->children());
    }
}
