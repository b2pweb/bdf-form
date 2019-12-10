<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class ValueGeneratorTest
 */
class ValueGeneratorTest extends TestCase
{
    /**
     *
     */
    public function test_generate_default()
    {
        $this->assertSame([], (new ValueGenerator())->generate($this->createMock(ElementInterface::class)));
    }

    /**
     *
     */
    public function test_generate_with_className()
    {
        $this->assertInstanceOf(MyEntity::class, (new ValueGenerator(MyEntity::class))->generate($this->createMock(ElementInterface::class)));
    }

    /**
     *
     */
    public function test_generate_with_entity()
    {
        $entity = new MyEntity();
        $entity->foo = 'bar';

        $generated = (new ValueGenerator($entity))->generate($this->createMock(ElementInterface::class));

        $this->assertEquals($entity, $generated);
        $this->assertNotSame($entity, $generated);
    }

    /**
     *
     */
    public function test_generate_with_callback()
    {
        $entity = new MyEntity();
        $entity->foo = 'bar';

        $element = $this->createMock(ElementInterface::class);

        $generated = (new ValueGenerator(function ($element) use(&$param) {
            $param = $element;

            return ['foo' => 'bar'];
        }))->generate($element);

        $this->assertEquals(['foo' => 'bar'], $generated);
        $this->assertSame($element, $param);
    }
}

class MyEntity
{
    public $foo;
}
