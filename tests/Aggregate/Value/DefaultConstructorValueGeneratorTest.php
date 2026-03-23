<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\Aggregate\Value\Fixtures\MyEntity;
use Bdf\Form\ElementInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class DefaultConstructorValueGeneratorTest extends TestCase
{
    #[Test]
    public function generateWithoutAttachment()
    {
        $generator = new DefaultConstructorValueGenerator(MyEntity::class);
        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertInstanceOf(MyEntity::class, $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    #[Test]
    public function generateWithAttachment()
    {
        $generator = new DefaultConstructorValueGenerator(MyEntity::class);
        $attachment = new MyEntity();
        $attachment->foo = 'bar';

        $generator->attach($attachment);

        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertSame($attachment, $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    #[
        Test,
        TestWith([MyEntity::class]),
        TestWith([new stdClass()]),
        TestWith([[]]),
    ]
    public function attachOnyAllowSameType($value)
    {
        $this->expectException(InvalidArgumentException::class);

        $generator = new DefaultConstructorValueGenerator(MyEntity::class);
        $generator->attach($value);
    }
}
