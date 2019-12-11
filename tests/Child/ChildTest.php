<?php

namespace Bdf\Form\Child;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Http\ArrayOffsetHttpFields;
use Bdf\Form\Error\FormError;
use Bdf\Form\Filter\ClosureFilter;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Class ChildTest
 */
class ChildTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'));

        $this->assertEquals('child', $child->name());
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertSame($child, $child->element()->container());
    }

    /**
     *
     */
    public function test_parent()
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'));
        $parent = new Form(new ChildrenCollection());

        $this->assertSame($child, $child->setParent($parent));
        $this->assertSame($parent, $child->parent());

        $newParent = new Form(new ChildrenCollection());
        $newChild = $child->setParent($newParent);

        $this->assertNotSame($child, $newChild);
        $this->assertSame($newChild, $newChild->element()->container());
        $this->assertSame($newParent, $newChild->parent());
        $this->assertSame($parent, $child->parent());
    }

    /**
     *
     */
    public function test_import_with_array()
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [], new NotBlank(['message' => 'required error']), null, new Getter());
        $child->setParent(new Form(new ChildrenCollection()));

        $child->import(['child' => 'my value']);
        $this->assertSame('my value', $child->element()->value());
    }

    /**
     *
     */
    public function test_import_with_object()
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [], new NotBlank(['message' => 'required error']), null, new Getter());
        $child->setParent(new Form(new ChildrenCollection()));

        $child->import((object) ['child' => 'my value']);
        $this->assertSame('my value', $child->element()->value());
    }

    /**
     *
     */
    public function test_fill_with_array()
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [], new NotBlank(['message' => 'required error']), new Setter());
        $child->setParent(new Form(new ChildrenCollection()));
        $child->element()->import('my value');

        $target = [];
        $child->fill($target);

        $this->assertEquals(['child' => 'my value'], $target);
    }

    /**
     *
     */
    public function test_fill_with_object()
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [], new NotBlank(['message' => 'required error']), new Setter());
        $child->setParent(new Form(new ChildrenCollection()));
        $child->element()->import('my value');

        $target = (object) ['child' => null];
        $child->fill($target);

        $this->assertEquals('my value', $target->child);
    }

    /**
     * @dataProvider emptyValues
     */
    public function test_submit_empty($value)
    {
        $child = new Child('child', new StringElement(), new ArrayOffsetHttpFields('child'), [], null, new Setter());
        $child->setParent(new Form(new ChildrenCollection()));

        $this->assertTrue($child->submit($value));
        $this->assertNull($child->element()->value());
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
        $this->assertEquals(FormError::message('This value should not be equal to "value".'), $child->error());
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
