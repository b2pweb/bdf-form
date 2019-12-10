<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\LessThan;

/**
 * Class IntegerElementTest
 */
class IntegerElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new IntegerElement();

        $this->assertFalse($element->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new IntegerElement();

        $this->assertTrue($element->submit('5')->valid());
        $this->assertSame(5, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new IntegerElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new IntegerElement(new ConstraintValueValidator(new LessThan(2)));

        $this->assertFalse($element->submit('5')->valid());
        $this->assertSame(5, $element->value());
        $this->assertEquals('This value should be less than 2.', $element->error()->global());

        $this->assertTrue($element->submit('-5')->valid());
        $this->assertSame(-5, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new IntegerElement(null, $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new IntegerElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? $value + 2 : $value - 2;
        }));

        $element->submit(1)->valid();
        $this->assertSame(3, $element->value());
        $this->assertEquals(1, $element->httpValue());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new IntegerElement();

        $this->assertSame(5, $element->import(5)->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new IntegerElement();

        $this->assertSame('5', $element->import(5)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new IntegerElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $newElement = $element->setContainer($container);

        $this->assertNotSame($element, $newElement);
        $this->assertSame($container, $newElement->container());
    }

    /**
     *
     */
    public function test_root_without_container()
    {
        $element = new IntegerElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new IntegerElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent(new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }
}
