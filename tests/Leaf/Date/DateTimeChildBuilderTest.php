<?php

namespace Bdf\Form\Leaf\Date;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use PHPUnit\Framework\TestCase;

class DateTimeChildBuilderTest extends TestCase
{
    /**
     *
     */
    public function test_saveAsTimestamp()
    {
        $builder = new DateTimeChildBuilder('child', new DateTimeElementBuilder());

        $child = $builder
            ->immutable()
            ->getter()->setter()
            ->saveAsTimestamp()
            ->buildChild()
        ;

        $child->setParent($form = new Form(new ChildrenCollection()));
        $child->import(['child' => 123]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $child->element()->value());
        $this->assertEquals(123, $child->element()->value()->getTimestamp());

        $child->element()->import(new \DateTimeImmutable('2020-10-15 00:00:00'));
        $target = [];
        $child->fill($target);

        $this->assertSame(['child' => 1602712800], $target);

        $child->import(['child' => null]);
        $this->assertNull($child->element()->value());

        $child->fill($target);
        $this->assertSame(['child' => null], $target);
    }
}
