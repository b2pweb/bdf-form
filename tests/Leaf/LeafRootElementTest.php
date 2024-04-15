<?php

namespace Bdf\Form\Leaf;

use BadMethodCallException;
use Bdf\Form\Child\Child;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\RecursiveValidator;

/**
 * Class LeafRootElementTest
 */
class LeafRootElementTest extends TestCase
{
    /**
     *
     */
    public function test_submit()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $element->expects($this->once())->method('submit')->with('data');
        $this->assertSame($root, $root->submit('data'));
    }

    /**
     *
     */
    public function test_patch()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $element->expects($this->once())->method('patch')->with('data');
        $this->assertSame($root, $root->patch('data'));
    }

    /**
     *
     */
    public function test_import()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $element->expects($this->once())->method('import')->with('data');
        $this->assertSame($root, $root->import('data'));
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $element->expects($this->once())->method('value')->willReturn('value');
        $this->assertSame('value', $root->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $element->expects($this->once())->method('httpValue')->willReturn('value');
        $this->assertSame('value', $root->httpValue());
    }

    /**
     *
     */
    public function test_valid()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $element->expects($this->exactly(2))->method('valid')->willReturn(true);
        $this->assertTrue($root->valid());
        $this->assertFalse($root->failed());
    }

    /**
     *
     */
    public function test_error()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $error = FormError::message('error');

        $element->expects($this->once())->method('error')->willReturn($error);
        $this->assertSame($error, $root->error());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $this->assertNull($root->container());

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot set a container on a root element');

        $root->setContainer(new Child('foo', new StringElement()));
    }

    /**
     *
     */
    public function test_submitButton()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $this->assertNull($root->submitButton());
    }

    /**
     *
     */
    public function test_button()
    {
        $this->expectException(\OutOfBoundsException::class);
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $root->button('btn');
    }

    /**
     *
     */
    public function test_root()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $this->assertSame($root, $root->root());
    }

    /**
     *
     */
    public function test_getValidator()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $this->assertInstanceOf(RecursiveValidator::class, $root->getValidator());
    }

    /**
     *
     */
    public function test_getPropertyAccessor()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $this->assertInstanceOf(PropertyAccessor::class, $root->getPropertyAccessor());
    }

    /**
     *
     */
    public function test_constraintGroups()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $this->assertEquals([Constraint::DEFAULT_GROUP], $root->constraintGroups());
    }

    /**
     *
     */
    public function test_flags()
    {
        $element = $this->createMock(ElementInterface::class);
        $root = new LeafRootElement($element);

        $this->assertFalse($root->is('foo'));
        $this->assertFalse($root->is('bar'));

        $root->set('foo', true);
        $this->assertTrue($root->is('foo'));
        $this->assertFalse($root->is('bar'));

        $root->set('foo', false);
        $this->assertFalse($root->is('foo'));
        $this->assertFalse($root->is('bar'));
    }
}
