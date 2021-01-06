<?php

namespace Bdf\Form\Leaf\Date;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Leaf\LeafRootElement;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\LessThan;

/**
 * Class DateTimeTypeTest
 */
class DateTimeElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new DateTimeElement();

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
        $element = new DateTimeElement();

        $this->assertTrue($element->submit('2020-12-17T10:40:00+1000')->valid());
        $this->assertEquals(new DateTime('2020-12-17 10:40:00', new DateTimeZone('+1000')), $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_invalid_format()
    {
        $element = new DateTimeElement();

        $this->assertFalse($element->submit('invalid')->valid());
        $this->assertEquals('Invalid date format', $element->error()->global());
        $this->assertEquals('TRANSFORM_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new DateTimeElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new DateTimeElement(new ConstraintValueValidator(new LessThan(new DateTime('2000-01-05 15:00:00'))));

        $this->assertFalse($element->submit('2020-12-17T10:40:00+1000')->valid());
        $this->assertEquals(new DateTime('2020-12-17 10:40:00', new DateTimeZone('+1000')), $element->value());
        $this->assertEquals('This value should be less than 5 janv. 2000 à 15:00.', $element->error()->global());

        $this->assertTrue($element->submit('1980-12-17T10:40:00+1000')->valid());
        $this->assertEquals(new DateTime('1980-12-17 10:40:00', new DateTimeZone('+1000')), $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new DateTimeElement(null, $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_patch_null()
    {
        $element = new DateTimeElement();
        $element->import(new DateTime('2020-10-14 15:00:00'));

        $this->assertSame($element, $element->patch(null));
        $this->assertEquals(new DateTime('2020-10-14 15:00:00'), $element->value());
        $this->assertTrue($element->valid());
        $this->assertNull($element->error()->global());
    }

    /**
     *
     */
    public function test_patch_null_with_constraints_should_be_validated()
    {
        $element = (new DateTimeElementBuilder())->after(new DateTime('2030-10-14 15:00:00'))->buildElement();
        $element->import(new DateTime('2020-10-14 15:00:00'));

        $this->assertSame($element, $element->patch(null));
        $this->assertEquals(new DateTime('2020-10-14 15:00:00'), $element->value());
        $this->assertFalse($element->valid());
        $this->assertEquals('This value should be greater than 14 oct. 2030 à 15:00.', $element->error()->global());
    }

    /**
     *
     */
    public function test_patch_with_value()
    {
        $element = (new DateTimeElementBuilder())->after(new DateTime('2030-10-14 15:00:00'))->buildElement();

        $this->assertFalse($element->patch('2020-10-14T15:00:00Z')->valid());
        $this->assertEquals(new DateTime('2020-10-14T15:00:00Z'), $element->value());

        $this->assertTrue($element->patch('2040-10-14T15:00:00Z')->valid());
        $this->assertEquals(new DateTime('2040-10-14T15:00:00Z'), $element->value());
    }

    /**
     *
     */
    public function test_with_transformer_that_returns_DateTime_should_keep_the_value()
    {
        $date = new DateTime('2020-12-17 15:00:00');

        $element = new DateTimeElement(null, new ClosureTransformer(function ($value, $input, $toPhp) use($date) {
            return $toPhp ? $date : $value;
        }));

        $element->submit('2020-12-17T15:00:00Z');
        $this->assertSame($date, $element->value());
    }

    /**
     *
     */
    public function test_with_transformer_that_returns_string_should_format_the_value()
    {
        $element = new DateTimeElement(null, new ClosureTransformer(function ($value, $input, $toPhp) {
            return $toPhp ? base64_decode($value) : base64_encode($value);
        }));

        $element->submit('MjAyMC0xMi0xN1QxNTowMDowMFo=');
        $this->assertEquals(new DateTime('2020-12-17 15:00:00', new DateTimeZone('+0000')), $element->value());
        $this->assertEquals('MjAyMC0xMi0xN1QxNTowMDowMCswMDowMA==', $element->httpValue());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new DateTimeElement();

        $date = new DateTime('2000-01-05 15:00:00');
        $this->assertSame($date, $element->import($date)->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new DateTimeElement();

        $this->assertSame('2000-01-05T15:00:00+01:00', $element->import(new DateTime('2000-01-05 15:00:00'))->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new DateTimeElement();

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
        $element = new DateTimeElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new DateTimeElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent(new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }

    /**
     *
     */
    public function test_with_custom_format()
    {
        $element = new DateTimeElement(null, null, null, DateTime::class, 'd/m/Y H:i');

        $element->import(new DateTime('2020-12-17 15:00:00'));

        $this->assertEquals('17/12/2020 15:00', $element->httpValue());

        $element->submit('10/10/2030 14:30');
        $this->assertEquals('10/10/2030 14:30', $element->httpValue());
        $this->assertEquals(new DateTime('2030-10-10 14:30:00'), $element->value());
    }

    /**
     *
     */
    public function test_with_custom_date_class()
    {
        $element = new DateTimeElement(null, null, null, MyCustomDate::class);

        $element->submit('2020-12-17T15:00:00Z');
        $this->assertEquals(new MyCustomDate('2020-12-17 15:00:00', new DateTimeZone('+0000')), $element->value());
    }

    /**
     *
     */
    public function test_with_custom_date_class_with_transformer_that_returns_invalid_type_should_change_type()
    {
        $date = new DateTime('2020-12-17 15:00:00');

        $element = new DateTimeElement(null, new ClosureTransformer(function ($value, $input, $toPhp) use($date) {
            return $toPhp ? $date : $value;
        }), null, MyCustomDate::class);

        $element->submit('2020-12-17T15:00:00Z');
        $this->assertEquals(new MyCustomDate('2020-12-17 15:00:00', new DateTimeZone('+0100')), $element->value());
    }

    /**
     *
     */
    public function test_with_custom_date_timezone()
    {
        $element = new DateTimeElement(null, null, null, DateTime::class, DateTime::ATOM, new DateTimeZone('+1000'));

        $element->submit('2020-12-17T15:00:00Z');
        $this->assertEquals(new DateTime('2020-12-18 01:00:00', new DateTimeZone('+1000')), $element->value());
        $this->assertEquals('2020-12-18T01:00:00+10:00', $element->httpValue());
        $this->assertEquals(new DateTimeZone('+1000'), $element->value()->getTimezone());
    }

    /**
     *
     */
    public function test_view()
    {
        $element = new DateTimeElement();
        $element->import(new DateTime('2000-01-05 15:00:00'));

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="2000-01-05T15:00:00+01:00" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="2000-01-05T15:00:00+01:00" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('2000-01-05T15:00:00+01:00', $view->value());
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
        $element = (new DateTimeElementBuilder())->before(new DateTime('1980-10-14 15:00:00'))->required()->buildElement();
        $element->submit('2020-10-14T12:00:00Z');

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertInstanceOf(SimpleElementView::class, $view);
        $this->assertEquals('<input type="text" name="name" value="2020-10-14T12:00:00+00:00" required />', (string) $view);
        $this->assertEquals('my error', $view->onError('my error'));

        $this->assertEquals('2020-10-14T12:00:00+00:00', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertTrue($view->hasError());
        $this->assertEquals('This value should be less than 14 oct. 1980 à 15:00.', $view->error());
    }

    /**
     *
     */
    public function test_view_without_name()
    {
        $element = new DateTimeElement();

        $this->assertEquals('<input type="text" name="" value="" />', (string) $element->view());
    }

    /**
     *
     */
    public function test_child_builder_default()
    {
        $builder = new ChildBuilder('child', new DateTimeElementBuilder());
        $builder->default(new DateTime('2020-11-13 05:00:00'));
        $child = $builder->buildChild();

        $child->submit([]);
        $this->assertEquals(new DateTime('2020-11-13 05:00:00'), $child->element()->value());
        $this->assertEquals('2020-11-13T05:00:00+01:00', $child->element()->httpValue());
    }
}

class MyCustomDate extends DateTime
{

}