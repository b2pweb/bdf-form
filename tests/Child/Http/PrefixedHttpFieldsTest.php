<?php

namespace Bdf\Form\Child\Http;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Error\FormError;
use Bdf\Form\Filter\ClosureFilter;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Count;

/**
 * Class PrefixedHttpFieldsTest
 */
class PrefixedHttpFieldsTest extends TestCase
{
    /**
     * @dataProvider emptyValues
     */
    public function test_submit_empty($value)
    {
        $child = new Child('child', new ArrayElement(new StringElement()), new PrefixedHttpFields('child_'), [], null, null, new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit($value));
        $this->assertSame([], $child->element()->value());
    }

    /**
     * @dataProvider emptyValues
     */
    public function test_submit_empty_with_default_value($value)
    {
        $child = new Child('child', new ArrayElement(new StringElement()), new PrefixedHttpFields('child_'), [], null, ['default'], new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit($value));
        $this->assertEquals(['default'], $child->element()->value());
    }

    /**
     *
     */
    public function test_submit_not_empty()
    {
        $child = new Child('child', new ArrayElement(new StringElement()), new PrefixedHttpFields('child_'), [], null, null, new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit(['child_0' => 'foo', 'child_bar' => 'baz', 'other' => 42]));
        $this->assertSame([0 => 'foo', 'bar' => 'baz'], $child->element()->value());
    }

    /**
     *
     */
    public function test_submit_element_constraint_error()
    {
        $child = new Child('child', new ArrayElement(new StringElement(), null, new ConstraintValueValidator(new Count(['min' => 2]))), new PrefixedHttpFields('child_'), [], null, null, new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertFalse($child->submit(['child_0' => 'value']));
        $this->assertEquals(['value'], $child->element()->value());
        $this->assertEquals(FormError::message('This collection should contain 2 elements or more.'), $child->error());
    }

    /**
     *
     */
    public function test_submit_with_filters()
    {
        $child = new Child('child', new ArrayElement(new StringElement()), new PrefixedHttpFields('child_'), [new ClosureFilter(function ($value) { return array_change_key_case($value, CASE_UPPER); })]);
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit(['child_message' => 'hello world !']));
        $this->assertEquals(['MESSAGE' => 'hello world !'], $child->element()->value());
    }

    /**
     *
     */
    public function test_httpFields()
    {
        $child = new Child('child', new ArrayElement(new StringElement()), new PrefixedHttpFields('child_'));
        $child->setParent(new Form(new ChildrenCollection()));
        $child->element()->import(['value', 'foo' => 'bar']);

        $this->assertSame(['child_0' => 'value', 'child_foo' => 'bar'], $child->httpFields());
    }

    /**
     * @return array
     */
    public function emptyValues()
    {
        return [
            [null],
            [[]],
            [['child' => null]],
        ];
    }
}
