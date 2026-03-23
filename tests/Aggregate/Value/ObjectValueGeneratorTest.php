<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\Aggregate\Value\Fixtures\MyDto;
use Bdf\Form\Aggregate\Value\Fixtures\MyEntity;
use Bdf\Form\ElementInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class ObjectValueGeneratorTest extends TestCase
{
    #[Test]
    public function generateWithoutAttachment()
    {
        $generator = new ObjectValueGenerator($o = new MyEntity());
        $o->foo = 'bar';

        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertEquals($o, $value);
        $this->assertNotSame($o, $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    #[Test]
    public function generateWithAttachmentObject()
    {
        $generator = new ObjectValueGenerator(new MyEntity());
        $attachment = new MyEntity();
        $attachment->foo = 'bar';

        $generator->attach($attachment);

        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertSame($attachment, $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    #[
        Test,
        TestWith([MyDto::class]),
        TestWith([[]]),
        TestWith([new stdClass()]),
    ]
    public function attachOnyAllowSameType($value)
    {
        $this->expectException(InvalidArgumentException::class);

        $generator = new ObjectValueGenerator(new MyEntity());
        $generator->attach($value);
    }
}
