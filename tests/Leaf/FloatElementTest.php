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
 * Class FloatElementTest
 */
class FloatElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new FloatElement();

        $this->assertFalse($element->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new FloatElement();

        $this->assertTrue($element->submit('5.1')->valid());
        $this->assertSame(5.1, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new FloatElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new FloatElement(new ConstraintValueValidator(new LessThan(2)));

        $this->assertFalse($element->submit('5.1')->valid());
        $this->assertSame(5.1, $element->value());
        $this->assertEquals('This value should be less than 2.', $element->error()->global());

        $this->assertTrue($element->submit('-5.2')->valid());
        $this->assertSame(-5.2, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new FloatElement(null, $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new FloatElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? $value + 2 : $value - 2;
        }));

        $element->submit(1.2)->valid();
        $this->assertSame(3.2, $element->value());
        $this->assertEquals(1.2, $element->httpValue());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new FloatElement();

        $this->assertSame(5.1, $element->import(5.1)->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new FloatElement();

        $this->assertSame('5.1', $element->import(5.1)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new FloatElement();

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
        $element = new FloatElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new FloatElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent(new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }
}
