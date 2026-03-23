<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\Aggregate\Value\Fixtures\MyDto;
use Bdf\Form\ElementInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class ConstructorValueGeneratorTest extends TestCase
{
    #[Test]
    public function generateWithoutAttachment()
    {
        $generator = new ConstructorValueGenerator(MyDto::class);
        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertSame([], $value);
        $this->assertEquals(new MyDto('bar'), $generator->finalize(['foo' => 'bar']));
    }

    #[Test]
    public function generateWithAttachmentObject()
    {
        $generator = new ConstructorValueGenerator(MyDto::class);
        $attachment = new MyDto('bar');

        $generator->attach($attachment);

        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertSame($attachment, $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    #[Test]
    public function generateWithAttachmentArray()
    {
        $generator = new ConstructorValueGenerator(MyDto::class);
        $attachment = ['foo' => 'bar'];

        $generator->attach($attachment);

        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertSame($attachment, $value);
        $this->assertEquals(new MyDto('bar'), $generator->finalize($value));
    }

    #[
        Test,
        TestWith([MyDto::class]),
        TestWith([new stdClass()]),
    ]
    public function attachOnyAllowSameType($value)
    {
        $this->expectException(InvalidArgumentException::class);

        $generator = new ConstructorValueGenerator(MyDto::class);
        $generator->attach($value);
    }
}
