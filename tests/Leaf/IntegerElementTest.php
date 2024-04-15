<?php

namespace Bdf\Form\Leaf;

use BadMethodCallException;
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
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class IntegerElementTest
 */
class IntegerElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new IntegerElement();

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
        $element = new IntegerElement();

        $this->assertTrue($element->submit('5')->valid());
        $this->assertSame(5, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new IntegerElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new IntegerElement(new ConstraintValueValidator([new LessThan(2)]));

        $this->assertFalse($element->submit('5')->valid());
        $this->assertTrue($element->failed());
        $this->assertSame(5, $element->value());
        $this->assertEquals('This value should be less than 2.', $element->error()->global());

        $this->assertTrue($element->submit('-5')->valid());
        $this->assertSame(-5, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new IntegerElement(null, $transformer);

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
        $element = new IntegerElement(
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
        $element = new IntegerElement(
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
        $element = new IntegerElement();
        $element->import(1);

        $this->assertSame($element, $element->patch(null));
        $this->assertSame(1, $element->value());
        $this->assertTrue($element->valid());
        $this->assertNull($element->error()->global());
    }

    /**
     *
     */
    public function test_patch_null_with_constraints_should_be_validated()
    {
        $element = (new IntegerElementBuilder())->min(3)->buildElement();
        $element->import(1);

        $this->assertSame($element, $element->patch(null));
        $this->assertSame(1, $element->value());
        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('This value should be greater than or equal to 3.', $element->error()->global());
    }

    /**
     *
     */
    public function test_patch_with_value()
    {
        $element = (new IntegerElementBuilder())->min(3)->buildElement();
        $element->import(1);

        $this->assertFalse($element->patch(2)->valid());
        $this->assertSame(2, $element->value());

        $this->assertTrue($element->patch('3,3')->valid());
        $this->assertSame(3, $element->value());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new IntegerElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? $value + 2 : $value - 2;
        }));

        $element->submit(1)->valid();
        $this->assertSame(3, $element->value());
        $this->assertEquals(1, $element->httpValue());
    }

    /**
     * @dataProvider provideValidValues
     */
    public function test_import($value, $expected)
    {
        $element = new IntegerElement();

        $this->assertSame($expected, $element->import($value)->value());
    }

    public function provideValidValues()
    {
        return [
            [15, 15],
            [1.5, 1],
            [null, null],
            ['0', 0],
            ['1.2', 1],
        ];
    }

    /**
     * @dataProvider provideInvalidValue
     */
    public function test_import_invalid_type($value)
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('The import()\'ed value of a Bdf\Form\Leaf\IntegerElement must be numeric or null');
        $element = new IntegerElement();

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
            [''],
            ['foo'],
            [true],
            [false],
        ];
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new IntegerElement();

        $this->assertSame('5', $element->import(5)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new IntegerElement();

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
        $element = new IntegerElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new IntegerElement();

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
        $element = new IntegerElement();
        $element->import(42);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="number" name="name" value="42" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="number" name="name" value="42" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('42', $view->value());
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
        $element = (new IntegerElementBuilder())->min(5)->required()->buildElement();
        $element->import(42);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="number" name="name" value="42" required min="5" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="number" name="name" value="42" required min="5" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('42', $view->value());
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
        $element = (new IntegerElementBuilder())->min(5)->required()->buildElement();
        $element->submit(3);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="number" name="name" value="3" required min="5" />', (string) $view);
        $this->assertEquals('my error', $view->onError('my error'));

        $this->assertEquals('3', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertTrue($view->hasError());
        $this->assertEquals('This value should be greater than or equal to 5.', $view->error());
    }

    /**
     *
     */
    public function test_view_without_name()
    {
        $element = new IntegerElement();

        $this->assertEquals('<input type="number" name="" value="" />', (string) $element->view());
    }

    /**
     *
     */
    public function test_view_with_choice()
    {
        $element = (new IntegerElementBuilder())->raw()->choices([12, 62, 32])->required()->buildElement();
        $element->submit(32);

        $view = $element->view(HttpFieldPath::named('val'));

        $this->assertContainsOnly(ChoiceView::class, $view->choices());
        $this->assertCount(3, $view->choices());

        $this->assertSame('12', $view->choices()[0]->value());
        $this->assertFalse($view->choices()[0]->selected());
        $this->assertSame('62', $view->choices()[1]->value());
        $this->assertFalse($view->choices()[1]->selected());
        $this->assertSame('32', $view->choices()[2]->value());
        $this->assertTrue($view->choices()[2]->selected());

        $this->assertEquals(
            '<select foo="bar" name="val" required><option value="12">12</option><option value="62">62</option><option value="32" selected>32</option></select>'
            , (string) $view->foo('bar')
        );
    }

    /**
     *
     */
    public function test_view_with_choice_and_transformer()
    {
        $element = (new IntegerElementBuilder())
            ->choices([12, 62, 32])
            ->transformer(function ($value, $input, $toPhp) {
                return $toPhp ? hexdec($value) : dechex($value);
            })
            ->required()
            ->buildElement()
        ;
        $element->submit('20');

        $view = $element->view(HttpFieldPath::named('val'));

        $this->assertContainsOnly(ChoiceView::class, $view->choices());
        $this->assertCount(3, $view->choices());

        $this->assertSame('c', $view->choices()[0]->value());
        $this->assertFalse($view->choices()[0]->selected());
        $this->assertSame('3e', $view->choices()[1]->value());
        $this->assertFalse($view->choices()[1]->selected());
        $this->assertSame('20', $view->choices()[2]->value());
        $this->assertTrue($view->choices()[2]->selected());

        $this->assertEquals(
            '<select foo="bar" name="val" required><option value="c">12</option><option value="3e">62</option><option value="20" selected>32</option></select>'
            , (string) $view->foo('bar')
        );
    }

    /**
     *
     */
    public function test_error()
    {
        $element = (new IntegerElementBuilder())->satisfy(function() { return false; })->buildElement();
        $element->submit('0');

        $error = $element->error(HttpFieldPath::named('foo'));

        $this->assertEquals('foo', $error->field());
        $this->assertEquals('The value is invalid', $error->global());
        $this->assertEquals('CUSTOM_ERROR', $error->code());
        $this->assertEmpty($error->children());
    }
}
