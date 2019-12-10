<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Child\Child;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Validator\ConstraintValueValidator;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Class ArrayElementTest
 */
class ArrayElementTest extends TestCase
{
    /**
     *
     */
    public function test_defaults()
    {
        $element = new ArrayElement(new StringElement());

        $this->assertFalse($element->valid());
        $this->assertTrue($element->error()->empty());
        $this->assertArrayNotHasKey(0, $element);
        $this->assertSame([], $element->value());
        $this->assertSame([], $element->httpValue());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new ArrayElement(new StringElement());

        $this->assertTrue($element->submit(['foo', 'bar'])->valid());
        $this->assertSame(['foo', 'bar'], $element->value());
    }

    /**
     *
     */
    public function test_submit_empty()
    {
        $element = new ArrayElement(new StringElement());

        $this->assertTrue($element->submit(null)->valid());
        $this->assertSame([], $element->value());
    }

    /**
     *
     */
    public function test_submit_should_filter_empty_elements()
    {
        $element = new ArrayElement(new StringElement());

        $this->assertTrue($element->submit(['foo', null, 'bar'])->valid());
        $this->assertSame([0 => 'foo', 2 => 'bar'], $element->value());
    }

    /**
     *
     */
    public function test_submit_scalar()
    {
        $element = new ArrayElement(new StringElement());

        $this->assertTrue($element->submit('foo')->valid());
        $this->assertSame(['foo'], $element->value());
    }

    /**
     *
     */
    public function test_submit_with_element_error()
    {
        $element = new ArrayElement(new StringElement(new ConstraintValueValidator(new NotEqualTo('foo'))));

        $this->assertFalse($element->submit(['foo', 'bar'])->valid());
        $this->assertEquals([0 => 'This value should not be equal to "foo".'], $element->error()->toArray());
    }

    /**
     *
     */
    public function test_submit_with_transformer_error()
    {
        $element = new ArrayElement(new StringElement(), new ClosureTransformer(function () { throw new Exception('My error'); }));

        $this->assertFalse($element->submit(['foo', 'bar'])->valid());
        $this->assertSame([], $element->value());
        $this->assertEquals('My error', $element->error()->global());
    }

    /**
     *
     */
    public function test_submit_with_array_error()
    {
        $element = new ArrayElement(new StringElement(), null, new ConstraintValueValidator(new Count(['min' => 3])));

        $this->assertFalse($element->submit(['foo', 'bar'])->valid());
        $this->assertEquals('This collection should contain 3 elements or more.', $element->error()->global());

        $this->assertTrue($element->submit(['foo', 'bar', 'rab'])->valid());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new ArrayElement(new StringElement());

        $this->assertSame($element, $element->import(['foo', 'bar']));
        $this->assertSame(['foo', 'bar'], $element->value());
        $this->assertEquals('foo', $element[0]->element()->value());
        $this->assertSame($element, $element[0]->parent());
        $this->assertEquals('bar', $element[1]->element()->value());
        $this->assertSame($element, $element[1]->parent());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new ArrayElement(new StringElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? base64_decode($value) : base64_encode($value);
        })));

        $element->import(['foo', 'bar']);

        $this->assertSame(['Zm9v', 'YmFy'], $element->httpValue());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new ArrayElement(new StringElement());
        $parent = new Form(new ChildrenCollection());
        $element = $element->setContainer(new Child('child', $element));
        $element->container()->setParent($parent);

        $this->assertSame($parent->root(), $element->root());
    }

    /**
     *
     */
    public function test_array_access()
    {
        $element = new ArrayElement(new StringElement());

        $this->assertCount(0, $element);
        $this->assertSame([], iterator_to_array($element));

        $element->import(['foo', 'bar']);

        $this->assertCount(2, $element);
        $this->assertSame([$element[0], $element[1]], iterator_to_array($element));
        $this->assertEquals('foo', $element[0]->element()->value());
        $this->assertSame($element, $element[0]->parent());
        $this->assertEquals('bar', $element[1]->element()->value());
        $this->assertSame($element, $element[1]->parent());
    }

    /**
     *
     */
    public function test_clone()
    {
        $element = new ArrayElement(new StringElement());
        $element->import(['foo', 'bar']);

        $cloned = clone $element;

        $this->assertNotSame($element[0], $cloned[0]);
        $this->assertNotSame($element[1], $cloned[1]);

        $this->assertSame($element, $element[0]->parent());
        $this->assertSame($element, $element[1]->parent());

        $this->assertSame($cloned, $cloned[0]->parent());
        $this->assertSame($cloned, $cloned[1]->parent());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new ArrayElement(new StringElement(), new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? explode(',', $value) : implode(',', $value);
        }));

        $this->assertSame(['foo', 'bar'], $element->submit('foo,bar')->value());
        $this->assertSame('foo,bar', $element->httpValue());
    }
}
