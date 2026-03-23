<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class ValueGeneratorTest
 */
#[AllowMockObjectsWithoutExpectations]
class ValueGeneratorTest extends TestCase
{
    /**
     *
     */
    public function test_generate_default()
    {
        $this->assertSame([], (new ValueGenerator())->generate($this->createMock(ElementInterface::class)));
        $this->assertSame([], (new ValueGenerator())->finalize([]));
    }

    /**
     *
     */
    public function test_generate_with_className_no_constructor()
    {
        $generator = new ValueGenerator(Fixtures\MyEntity::class);
        $value = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertInstanceOf(Fixtures\MyEntity::class, $value);
        $this->assertSame($value, $generator->finalize($value));
    }

    /**
     *
     */
    public function test_generate_with_entity()
    {
        $entity = new Fixtures\MyEntity();
        $entity->foo = 'bar';

        $generator = (new ValueGenerator($entity));

        $generated = $generator->generate($this->createMock(ElementInterface::class));

        $this->assertEquals($entity, $generated);
        $this->assertNotSame($entity, $generated);
        $this->assertSame($generated, $generator->finalize($generated));
    }

    /**
     *
     */
    public function test_generate_with_callback()
    {
        $entity = new Fixtures\MyEntity();
        $entity->foo = 'bar';
        $generator = new ValueGenerator(function ($element) use(&$param) {
            $param = $element;

            return ['foo' => 'bar'];
        });

        $element = $this->createMock(ElementInterface::class);

        $generated = $generator->generate($element);

        $this->assertEquals(['foo' => 'bar'], $generated);
        $this->assertSame($element, $param);
        $this->assertSame($generated, $generator->finalize($generated));
    }

    /**
     *
     */
    public function test_attach_entity_should_return_the_same_instance()
    {
        $entity = new Fixtures\MyEntity();
        $entity->foo = 'bar';

        $element = $this->createMock(ElementInterface::class);
        $generator = new ValueGenerator();
        $generator->attach($entity);

        $generated = $generator->generate($element);

        $this->assertSame($entity, $generated);
        $this->assertSame($generated, $generator->finalize($generated));
    }

    /**
     *
     */
    public function test_attach_with_callback()
    {
        $entity = new Fixtures\MyEntity();
        $entity->foo = 'bar';

        $element = $this->createMock(ElementInterface::class);
        $generator = new ValueGenerator();
        $generator->attach(function ($element) use(&$param) {
            $param = $element;

            return ['foo' => 'bar'];
        });

        $generated = $generator->generate($element);

        $this->assertEquals(['foo' => 'bar'], $generated);
        $this->assertSame($element, $param);
        $this->assertSame($generated, $generator->finalize($generated));
    }

    public function test_with_dto_constructor()
    {
        $element = $this->createMock(ElementInterface::class);
        $generator = new ValueGenerator();

        $generator->attach(Fixtures\MyDto::class);

        $this->assertSame([], $generator->generate($element));
        $this->assertEquals(new Fixtures\MyDto('bar'), $generator->finalize(['foo' => 'bar']));
    }
}
