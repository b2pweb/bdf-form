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

class BooleanStringElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new BooleanStringElement();

        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     * @testWith [true]
     *           [1]
     *           ["1"]
     *           ["true"]
     *           ["on"]
     *           ["yes"]
     *           [" True "]
     */
    public function test_submit_true($value)
    {
        $element = new BooleanStringElement();

        $this->assertTrue($element->submit($value)->valid());
        $this->assertTrue($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     * @testWith [false]
     *           [0]
     *           ["0"]
     *           ["false"]
     *           ["off"]
     *           ["no"]
     *           [" False "]
     */
    public function test_submit_false($value)
    {
        $element = new BooleanStringElement();

        $this->assertTrue($element->submit($value)->valid());
        $this->assertFalse($element->value());
        $this->assertTrue($element->error()->empty());
    }

    public function test_submit_invalid()
    {
        $element = new BooleanStringElement();

        $this->assertTrue($element->submit('invalid')->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new BooleanStringElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_empty()
    {
        $element = new BooleanStringElement();

        $this->assertTrue($element->submit('')->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());

        $this->assertTrue($element->submit([])->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new BooleanStringElement(new ConstraintValueValidator([new NotBlank()]));

        $this->assertFalse($element->submit('invalid')->valid());
        $this->assertTrue($element->failed());
        $this->assertNull($element->value());
        $this->assertEquals('This value should not be blank.', $element->error()->global());

        $this->assertTrue($element->submit('true')->valid());
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
        $element = new BooleanStringElement(null, $transformer);

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
        $element = new BooleanStringElement(
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
        $element = new BooleanStringElement(
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
        $element = new BooleanStringElement(null, new ClosureTransformer(function ($value, BooleanStringElement $input, $toPhp) {
            if ($toPhp) {
                switch ($value) {
                    case '-':
                        return false;
                    case '+':
                        return true;
                }
            } else {
                return $input->value() ? '+' : '-';
            }
        }));

        $element->submit('-');
        $this->assertFalse($element->value());
        $this->assertSame('-', $element->httpValue());
    }

    /**
     * @dataProvider provideValidValues
     */
    public function test_import($value, $expected)
    {
        $element = new BooleanStringElement();

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
        $this->expectExceptionMessage('The import()\'ed value of a Bdf\Form\Leaf\BooleanStringElement must be a scalar value or null');
        $element = new BooleanStringElement();

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
        $element = new BooleanStringElement();

        $this->assertSame('true', $element->import(true)->httpValue());
        $this->assertSame('false', $element->import(false)->httpValue());
        $this->assertNull($element->import(null)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new BooleanStringElement();

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
        $element = new BooleanStringElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new BooleanStringElement();

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
        $element = new BooleanStringElement();
        $element->import(true);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="text" name="name" value="true" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="true" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('true', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertFalse($view->required());
        $this->assertEmpty($view->constraints());

        $element->import(false);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="text" name="name" value="false" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="false" />', (string) $view->id('foo')->class('form-element'));

        $this->assertEquals('false', $view->value());

        $this->assertEquals('<input type="text" name="" value="false" />', (string) $element->view());
    }

    /**
     *
     */
    public function test_view_not_submitted()
    {
        $element = new BooleanStringElement();

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="text" name="name" value="" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

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
        $element = (new BooleanElementBuilder())->booleanString()->satisfy(function() { return false; })->buildElement();
        $element->submit('ok');

        $error = $element->error(HttpFieldPath::named('foo'));

        $this->assertEquals('foo', $error->field());
        $this->assertEquals('The value is invalid', $error->global());
        $this->assertEquals('CUSTOM_ERROR', $error->code());
        $this->assertEmpty($error->children());
    }
}
