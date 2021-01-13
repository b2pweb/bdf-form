<?php

namespace Bdf\Form\Child\Http;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Error\FormError;
use Bdf\Form\Filter\ClosureFilter;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Class ArrayOffsetHttpFieldsTest
 */
class ArrayOffsetHttpFieldsTest extends TestCase
{
    /**
     * @dataProvider emptyValues
     */
    public function test_submit_empty($value)
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [], null, new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit($value));
        $this->assertEmpty($child->element()->value());
    }

    /**
     *
     */
    public function test_empty_values_with_default()
    {
        $field = new ArrayOffsetHttpFields('child');

        $this->assertSame('default', $field->extract([], 'default'));
        $this->assertSame('default', $field->extract('', 'default'));
        $this->assertSame('default', $field->extract(null, 'default'));
        $this->assertSame('default', $field->extract(['child' => ''], 'default'));
        $this->assertSame('0', $field->extract(['child' => '0'], 'default'));
    }

    /**
     *
     */
    public function test_empty_values_without_default()
    {
        $field = new ArrayOffsetHttpFields('child');

        $this->assertSame(null, $field->extract([], null));
        $this->assertSame(null, $field->extract('', null));
        $this->assertSame(null, $field->extract(null, null));
        $this->assertSame('', $field->extract(['child' => ''], null));
    }

    /**
     *
     */
    public function test_contains()
    {
        $field = new ArrayOffsetHttpFields('child');

        $this->assertFalse($field->contains([]));
        $this->assertFalse($field->contains(null));
        $this->assertFalse($field->contains(''));
        $this->assertFalse($field->contains(['foo' => 'xxx']));
        $this->assertTrue($field->contains(['child' => 'xxx']));
    }

    /**
     * @dataProvider emptyValues
     */
    public function test_submit_empty_with_default_value($value)
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [], 'default', new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit($value));
        $this->assertEquals('default', $child->element()->value());
    }

    /**
     * @dataProvider notEmptyValues
     */
    public function test_submit_not_empty($value)
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [], null, new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit(['child' => $value]));
        $this->assertEquals($value, $child->element()->value());
    }

    /**
     *
     */
    public function test_submit_element_constraint_error()
    {
        $child = new Child('child', new StringElement(new ConstraintValueValidator(new NotEqualTo('value'))), new ArrayOffsetHttpFields('child'), [], null, new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertFalse($child->submit(['child' => 'value']));
        $this->assertEquals('value', $child->element()->value());
        $this->assertEquals(FormError::message('This value should not be equal to "value".', 'IS_EQUAL_ERROR'), $child->error());
    }

    /**
     *
     */
    public function test_submit_with_filters()
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [new ClosureFilter(function ($value) { return strtoupper($value); }), new ClosureFilter(function ($value) { return substr($value, 0, 3); })]);
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit(['child' => 'hello world !']));
        $this->assertEquals('HEL', $child->element()->value());
    }

    /**
     *
     */
    public function test_httpFields()
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [new ClosureFilter(function ($value) { return strtoupper($value); }), new ClosureFilter(function ($value) { return substr($value, 0, 3); })]);
        $child->setParent(new Form(new ChildrenCollection()));
        $child->element()->import('value');

        $this->assertSame(['child' => 'value'], $child->httpFields());
    }

    /**
     *
     */
    public function test_get()
    {
        $fields = new ArrayOffsetHttpFields('child');

        $this->assertEquals('child', $fields->get());
        $this->assertEquals('foo[child]', $fields->get(HttpFieldPath::named('foo')));
        $this->assertEquals('foo_child', $fields->get(HttpFieldPath::prefixed('foo_')));
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
            [['child' => []]],
            [['child' => '']],
        ];
    }

    /**
     * @return array
     */
    public function notEmptyValues()
    {
        return [
            ['0'],
            [false],
            [0],
            [0.0],
            ['hello world !'],
        ];
    }
}
