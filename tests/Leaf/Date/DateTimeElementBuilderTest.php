<?php

namespace Bdf\Form\Leaf\Date;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\ChildBuilder;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\LessThan;

/**
 * Class DateTimeElementBuilderTest
 */
class DateTimeElementBuilderTest extends TestCase
{
    /**
     * @var DateTimeElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new DateTimeElementBuilder();
    }

    /**
     *
     */
    public function test_buildElement()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(DateTimeElement::class, $element);
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new LessThan(new \DateTime('2020-11-15 15:20:00')))->buildElement();

        $this->assertFalse($element->submit('2020-12-15T15:20:00Z')->valid());
        $this->assertTrue($element->submit('2015-12-15T15:20:00Z')->valid());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = $this->builder->transformer(function ($value, $element, $toPhp) {
            return $toPhp ? base64_decode($value) : base64_encode($value);
        })->buildElement();

        $this->assertEquals(new \DateTime('2020-12-17T15:00:00Z'), $element->submit('MjAyMC0xMi0xN1QxNTowMDowMCswMDowMA==')->value());
        $this->assertSame('MjAyMC0xMi0xN1QxNTowMDowMCswMDowMA==', $element->httpValue());
    }

    /**
     *
     */
    public function test_className()
    {
        $element = $this->builder->className(DateTimeImmutable::class)->buildElement();

        $this->assertEquals(new DateTimeImmutable('2020-12-17T15:00:00Z'), $element->submit('2020-12-17T15:00:00Z')->value());
    }

    /**
     *
     */
    public function test_immutable()
    {
        $element = $this->builder->immutable()->buildElement();

        $this->assertEquals(new DateTimeImmutable('2020-12-17T15:00:00Z'), $element->submit('2020-12-17T15:00:00Z')->value());
    }

    /**
     *
     */
    public function test_format()
    {
        $element = $this->builder->format('d/m/Y H:i')->buildElement();

        $this->assertEquals(new DateTimeImmutable('2020-12-17 15:00:00'), $element->submit('17/12/2020 15:00')->value());
    }

    /**
     *
     */
    public function test_timezone()
    {
        $element = $this->builder->timezone('+1000')->buildElement();

        $this->assertEquals(new DateTimeImmutable('2020-12-17T15:00:00Z', new DateTimeZone('+1000')), $element->submit('2020-12-17T15:00:00Z')->value());
        $this->assertEquals(new DateTimeZone('+1000'), $element->submit('2020-12-17T15:00:00Z')->value()->getTimezone());
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->builder->value($v = new \DateTime('2020-11-15 15:20:00'))->buildElement();

        $this->assertSame($v, $element->value());
    }

    /**
     *
     */
    public function test_required()
    {
        $element = $this->builder->required()->buildElement();

        $element->submit(null);
        $this->assertEquals('This value should not be blank.', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $element = $this->builder->required('my message')->buildElement();

        $element->submit(null);
        $this->assertEquals('my message', $element->error()->global());
    }

    /**
     *
     */
    public function test_before()
    {
        $element = $this->builder->before(new \DateTime('2020-11-15 15:33:00+0100'), 'My message')->buildElement();

        $this->assertFalse($element->submit('2020-12-15T15:33:00+0100')->valid());
        $this->assertEquals('My message', $element->error()->global());
        $this->assertTrue($element->submit('2015-12-15T15:33:00+0100')->valid());
        $this->assertFalse($element->submit('2020-11-15T15:33:00+0100')->valid());
    }

    /**
     *
     */
    public function test_before_or_equals()
    {
        $element = $this->builder->before(new \DateTime('2020-11-15 15:33:00+0100'), 'My message', true)->buildElement();

        $this->assertFalse($element->submit('2020-12-15T15:33:00+0100')->valid());
        $this->assertEquals('My message', $element->error()->global());
        $this->assertTrue($element->submit('2015-12-15T15:33:00+0100')->valid());
        $this->assertTrue($element->submit('2020-11-15T15:33:00+0100')->valid());
    }

    /**
     *
     */
    public function test_after()
    {
        $element = $this->builder->after(new \DateTime('2020-11-15 15:33:00+0100'), 'My message')->buildElement();

        $this->assertTrue($element->submit('2020-12-15T15:33:00+0100')->valid());
        $this->assertFalse($element->submit('2015-12-15T15:33:00+0100')->valid());
        $this->assertFalse($element->submit('2020-11-15T15:33:00+0100')->valid());
        $this->assertEquals('My message', $element->error()->global());
    }

    /**
     *
     */
    public function test_after_or_equals()
    {
        $element = $this->builder->after(new \DateTime('2020-11-15 15:33:00+0100'), 'My message', true)->buildElement();

        $this->assertTrue($element->submit('2020-12-15T15:33:00+0100')->valid());
        $this->assertFalse($element->submit('2015-12-15T15:33:00+0100')->valid());
        $this->assertEquals('My message', $element->error()->global());
        $this->assertTrue($element->submit('2020-11-15T15:33:00+0100')->valid());
    }

    /**
     *
     */
    public function test_beforeField()
    {
        $builder = new ChildBuilder('start', $this->builder);
        $builder->depends('end')->beforeField('end', 'My error');

        $form = new Form(new ChildrenCollection([
            $builder->buildChild(),
            (new ChildBuilder('end', new DateTimeElementBuilder()))->buildChild(),
        ]));

        $form['end']->element()->import(new \DateTime('2020-12-18 10:00:00'));

        $this->assertFalse($form['start']->element()->submit('2020-12-18T12:00:00+0100')->valid());
        $this->assertEquals('My error', $form['start']->element()->error()->global());
        $this->assertFalse($form['start']->element()->submit('2020-12-18T10:00:00+0100')->valid());
        $this->assertTrue($form['start']->element()->submit('2020-12-18T08:00:00+0100')->valid());
    }

    /**
     *
     */
    public function test_beforeField_or_equal()
    {
        $builder = new ChildBuilder('start', $this->builder);
        $builder->depends('end')->beforeField('end', 'My error', true);

        $form = new Form(new ChildrenCollection([
            $builder->buildChild(),
            (new ChildBuilder('end', new DateTimeElementBuilder()))->buildChild(),
        ]));

        $form['end']->element()->import(new \DateTime('2020-12-18 10:00:00'));

        $this->assertFalse($form['start']->element()->submit('2020-12-18T12:00:00+0100')->valid());
        $this->assertEquals('My error', $form['start']->element()->error()->global());
        $this->assertTrue($form['start']->element()->submit('2020-12-18T10:00:00+0100')->valid());
        $this->assertTrue($form['start']->element()->submit('2020-12-18T08:00:00+0100')->valid());
    }

    /**
     *
     */
    public function test_afterField()
    {
        $builder = new ChildBuilder('end', $this->builder);
        $builder->depends('start')->afterField('start', 'My error');

        $form = new Form(new ChildrenCollection([
            $builder->buildChild(),
            (new ChildBuilder('start', new DateTimeElementBuilder()))->buildChild(),
        ]));

        $form['start']->element()->import(new \DateTime('2020-12-18 10:00:00'));

        $this->assertTrue($form['end']->element()->submit('2020-12-18T12:00:00+0100')->valid());
        $this->assertFalse($form['end']->element()->submit('2020-12-18T10:00:00+0100')->valid());
        $this->assertFalse($form['end']->element()->submit('2020-12-18T08:00:00+0100')->valid());
        $this->assertEquals('My error', $form['end']->element()->error()->global());
    }

    /**
     *
     */
    public function test_afterField_or_equal()
    {
        $builder = new ChildBuilder('end', $this->builder);
        $builder->depends('start')->afterField('start', 'My error', true);

        $form = new Form(new ChildrenCollection([
            $builder->buildChild(),
            (new ChildBuilder('start', new DateTimeElementBuilder()))->buildChild(),
        ]));

        $form['start']->element()->import(new \DateTime('2020-12-18 10:00:00'));

        $this->assertTrue($form['end']->element()->submit('2020-12-18T12:00:00+0100')->valid());
        $this->assertTrue($form['end']->element()->submit('2020-12-18T10:00:00+0100')->valid());
        $this->assertFalse($form['end']->element()->submit('2020-12-18T08:00:00+0100')->valid());
        $this->assertEquals('My error', $form['end']->element()->error()->global());
    }
}
