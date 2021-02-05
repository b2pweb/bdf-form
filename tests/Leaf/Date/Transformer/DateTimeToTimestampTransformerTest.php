<?php

namespace Bdf\Form\Leaf\Date\Transformer;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\ElementInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class DateTimeToTimestampTransformerTest
 */
class DateTimeToTimestampTransformerTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $builder = new FormBuilder();
        $builder->dateTime('foo')->getter()->setter()->modelTransformer(new DateTimeToTimestampTransformer());
        $form = $builder->buildElement();

        $form->import(['foo' => 123]);
        $this->assertSame(['foo' => 123], $form->value());

        $this->assertEquals(new \DateTime('@123'), $form['foo']->element()->value());
        $this->assertEquals(new \DateTimeZone(date_default_timezone_get()), $form['foo']->element()->value()->getTimezone());
    }

    /**
     *
     */
    public function test_default_with_className()
    {
        $builder = new FormBuilder();
        $builder->dateTime('foo')->getter()->setter()->modelTransformer(new DateTimeToTimestampTransformer(CustomDateTime::class));
        $form = $builder->buildElement();

        $form->import(['foo' => 123]);
        $this->assertSame(['foo' => 123], $form->value());

        $this->assertInstanceOf(CustomDateTime::class, $form['foo']->element()->value());
        $this->assertEquals(new CustomDateTime('@123'), $form['foo']->element()->value());
        $this->assertEquals(new \DateTimeZone(date_default_timezone_get()), $form['foo']->element()->value()->getTimezone());
    }

    /**
     *
     */
    public function test_default_with_className_and_timezone()
    {
        $builder = new FormBuilder();
        $builder->dateTime('foo')->getter()->setter()->modelTransformer(new DateTimeToTimestampTransformer(CustomDateTime::class, new \DateTimeZone('Asia/Shanghai')));
        $form = $builder->buildElement();

        $form->import(['foo' => 123]);
        $this->assertSame(['foo' => 123], $form->value());

        $this->assertEquals(new CustomDateTime('@123'), $form['foo']->element()->value());
        $this->assertEquals(new \DateTimeZone('Asia/Shanghai'), $form['foo']->element()->value()->getTimezone());
    }

    /**
     *
     */
    public function test_invalid_value()
    {
        $this->expectException(\InvalidArgumentException::class);

        $transformer = new DateTimeToTimestampTransformer();
        $transformer->transformToHttp('invalid', $this->createMock(ElementInterface::class));
    }
}

class CustomDateTime extends \DateTime
{

}