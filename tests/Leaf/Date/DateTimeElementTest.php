<?php

namespace Bdf\Form\Leaf\Date;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
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
//
//    /**
//     *
//     */
//    public function test_view_with_constraints()
//    {
//        $element = (new DateTimeElementBuilder())->min(5)->required()->buildElement();
//        $element->import(42);
//
//        $view = $element->view(HttpFieldPath::named('name'));
//
//        $this->assertInstanceOf(SimpleElementView::class, $view);
//        $this->assertEquals('<input type="number" name="name" value="42" required min="5" />', (string) $view);
//        $this->assertEquals('<input id="foo" class="form-element" type="number" name="name" value="42" required min="5" />', (string) $view->id('foo')->class('form-element'));
//        $this->assertNull($view->onError('my error'));
//
//        $this->assertEquals('42', $view->value());
//        $this->assertEquals('name', $view->name());
//        $this->assertFalse($view->hasError());
//        $this->assertNull($view->error());
//        $this->assertTrue($view->required());
//        $this->assertEquals([NotBlank::class => [], GreaterThanOrEqual::class => ['value' => 5]], $view->constraints());
//    }
//
//    /**
//     *
//     */
//    public function test_view_with_error()
//    {
//        $element = (new DateTimeElementBuilder())->min(5)->required()->buildElement();
//        $element->submit(3);
//
//        $view = $element->view(HttpFieldPath::named('name'));
//
//        $this->assertInstanceOf(SimpleElementView::class, $view);
//        $this->assertEquals('<input type="number" name="name" value="3" required min="5" />', (string) $view);
//        $this->assertEquals('my error', $view->onError('my error'));
//
//        $this->assertEquals('3', $view->value());
//        $this->assertEquals('name', $view->name());
//        $this->assertTrue($view->hasError());
//        $this->assertEquals('This value should be greater than or equal to 5.', $view->error());
//    }

    /**
     *
     */
    public function test_view_without_name()
    {
        $element = new DateTimeElement();

        $this->assertEquals('<input type="text" name="" value="" />', (string) $element->view());
    }
//
//    /**
//     *
//     */
//    public function test_view_with_choice()
//    {
//        $element = (new DateTimeElementBuilder())->raw()->choices([12, 62, 32])->required()->buildElement();
//        $element->submit(32);
//
//        $view = $element->view(HttpFieldPath::named('val'));
//
//        $this->assertContainsOnly(ChoiceView::class, $view->choices());
//        $this->assertCount(3, $view->choices());
//
//        $this->assertSame('12', $view->choices()[0]->value());
//        $this->assertFalse($view->choices()[0]->selected());
//        $this->assertSame('62', $view->choices()[1]->value());
//        $this->assertFalse($view->choices()[1]->selected());
//        $this->assertSame('32', $view->choices()[2]->value());
//        $this->assertTrue($view->choices()[2]->selected());
//
//        $this->assertEquals(
//            '<select foo="bar" name="val" required><option value="12">12</option><option value="62">62</option><option value="32" selected>32</option></select>'
//            , (string) $view->foo('bar')
//        );
//    }
//
//    /**
//     *
//     */
//    public function test_view_with_choice_and_transformer()
//    {
//        $element = (new DateTimeElementBuilder())
//            ->choices([12, 62, 32])
//            ->transformer(function ($value, $input, $toPhp) {
//                return $toPhp ? hexdec($value) : dechex($value);
//            })
//            ->required()
//            ->buildElement()
//        ;
//        $element->submit('20');
//
//        $view = $element->view(HttpFieldPath::named('val'));
//
//        $this->assertContainsOnly(ChoiceView::class, $view->choices());
//        $this->assertCount(3, $view->choices());
//
//        $this->assertSame('c', $view->choices()[0]->value());
//        $this->assertFalse($view->choices()[0]->selected());
//        $this->assertSame('3e', $view->choices()[1]->value());
//        $this->assertFalse($view->choices()[1]->selected());
//        $this->assertSame('20', $view->choices()[2]->value());
//        $this->assertTrue($view->choices()[2]->selected());
//
//        $this->assertEquals(
//            '<select foo="bar" name="val" required><option value="c">12</option><option value="3e">62</option><option value="20" selected>32</option></select>'
//            , (string) $view->foo('bar')
//        );
//    }
}

class MyCustomDate extends DateTime
{

}