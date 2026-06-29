<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\Aggregate\Value\Fixtures\MyEntity;
use Bdf\Form\ElementInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SimpleValueGeneratorTest extends TestCase
{
    #[Test]
    public function generateWithoutAttachment()
    {
        $generator = new SimpleValueGenerator($o = new MyEntity());
        $o->foo = 'bar';

        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertSame($o, $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    #[Test]
    public function generateWithoutAttachmentArray()
    {
        $generator = new SimpleValueGenerator(['foo' => 'bar']);

        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertSame(['foo' => 'bar'], $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    #[Test]
    public function generateWithAttachmentObject()
    {
        $generator = new SimpleValueGenerator(new MyEntity());
        $attachment = new MyEntity();
        $attachment->foo = 'bar';

        $generator->attach($attachment);

        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertSame($attachment, $value);
        $this->assertSame($value, $generator->finalize($value));
    }
}
