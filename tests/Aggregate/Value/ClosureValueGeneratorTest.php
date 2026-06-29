<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class ClosureValueGeneratorTest extends TestCase
{
    #[Test]
    public function generate_without_attachment()
    {
        $generator = new ClosureValueGenerator(fn (ElementInterface $e) => ['foo' => 'bar']);

        $value = $generator->generate($this->createMock(ElementInterface::class));
        $this->assertSame(['foo' => 'bar'], $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    #[Test]
    public function generate_with_attachment()
    {
        $generator = new ClosureValueGenerator(fn (ElementInterface $e) => ['foo' => 'bar']);
        $attachment = new stdClass();
        $generator->attach($attachment);

        $value = $generator->generate($this->createMock(ElementInterface::class));
        $this->assertSame($attachment, $value);
        $this->assertSame($value, $generator->finalize($value));
    }
}
