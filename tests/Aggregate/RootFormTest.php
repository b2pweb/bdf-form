<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Button\SubmitButton;
use Bdf\Form\Button\View\ButtonView;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\ArrayOffsetHttpFields;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\Registry\Registry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\RecursiveValidator;

/**
 * Class RootFormTest
 */
class RootFormTest extends TestCase
{
    /**
     *
     */
    public function test_submit_with_button()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]));

        $root = new RootForm(
            $form,
            [
                $btn1 = new SubmitButton('btn1', 'ok', ['btn1']),
                $btn2 = new SubmitButton('btn2', 'ok', ['btn2']),
                $btn3 = new SubmitButton('btn3', 'ok'),
            ]
        );

        $root->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
            'btn1' => 'ok',
        ]);

        $this->assertSame($btn1, $root->submitButton());
        $this->assertEquals(['btn1'], $root->constraintGroups());
        $this->assertEquals([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
            'btn1' => 'ok',
        ], $root->httpValue());

        $root->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
            'btn2' => 'ok',
        ]);

        $this->assertSame($btn2, $root->submitButton());
        $this->assertEquals(['btn2'], $root->constraintGroups());
        $this->assertEquals([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
            'btn2' => 'ok',
        ], $root->httpValue());

        $root->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
            'btn2' => 'nok',
        ]);

        $this->assertNull($root->submitButton());
        $this->assertEquals([Constraint::DEFAULT_GROUP], $root->constraintGroups());
        $this->assertEquals([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ], $root->httpValue());

        $root->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
            'btn3' => 'ok',
        ]);

        $this->assertEquals([Constraint::DEFAULT_GROUP], $root->constraintGroups());
        $this->assertEquals([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
            'btn3' => 'ok',
        ], $root->httpValue());
    }

    /**
     *
     */
    public function test_patch_with_button()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]));

        $root = new RootForm(
            $form,
            [
                $btn1 = new SubmitButton('btn1', 'ok', ['btn1']),
                $btn2 = new SubmitButton('btn2', 'ok', ['btn2']),
            ]
        );

        $root->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
            'btn1' => 'ok',
        ]);

        $this->assertSame($btn1, $root->submitButton());

        $root->patch(['btn2' => 'ok']);

        $this->assertSame($btn2, $root->submitButton());
        $this->assertEquals('John', $root['firstName']->element()->value());
        $this->assertEquals('Smith', $root['lastName']->element()->value());

        $root->patch([]);

        $this->assertNull($root->submitButton());
        $this->assertEquals('John', $root['firstName']->element()->value());
        $this->assertEquals('Smith', $root['lastName']->element()->value());
    }

    /**
     *
     */
    public function test_import()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->getter()->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->getter()->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->getter()->buildChild(),
        ]));

        $root = new RootForm($form);

        $root->import([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ]);

        $this->assertEquals('John', $root['firstName']->element()->value());
        $this->assertEquals('Smith', $root['lastName']->element()->value());
        $this->assertEquals(4, $root['id']->element()->value());
    }

    /**
     *
     */
    public function test_getValidator()
    {
        $form = new RootForm(new Form(new ChildrenCollection()));
        $this->assertInstanceOf(RecursiveValidator::class, $form->getValidator());
        $this->assertSame($form->getValidator(), $form->getValidator());
    }

    /**
     *
     */
    public function test_getPropertyAccessor()
    {
        $form = new RootForm(new Form(new ChildrenCollection()));
        $this->assertInstanceOf(PropertyAccessor::class, $form->getPropertyAccessor());
        $this->assertSame($form->getPropertyAccessor(), $form->getPropertyAccessor());
    }

    /**
     *
     */
    public function test_delegation()
    {
        $form = new RootForm($inner = new Form(new ChildrenCollection([
            new Child('value', new IntegerElement(), new ArrayOffsetHttpFields('value'))
        ])));

        $this->assertSame($form, $form->submit(['value' => '42']));
        $this->assertSame($form, $form->import([]));
        $this->assertSame([], $form->value());
        $this->assertSame(['value' => '42'], $form->httpValue());
        $this->assertTrue($form->valid());
        $this->assertTrue($form->error()->empty());
        $this->assertNull($form->container());
        $this->assertSame($form, $form->root());
    }

    /**
     *
     */
    public function test_view()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]));

        $root = new RootForm(
            $form,
            [
                $btn1 = new SubmitButton('btn1', 'ok', ['btn1']),
                $btn2 = new SubmitButton('btn2', 'ok', ['btn2']),
            ]
        );

        $view = $root->view();

        $this->assertInstanceOf(SimpleElementView::class, $view['firstName']);
        $this->assertInstanceOf(SimpleElementView::class, $view['lastName']);
        $this->assertInstanceOf(SimpleElementView::class, $view['id']);
        $this->assertInstanceOf(ButtonView::class, $view['btn1']);
        $this->assertInstanceOf(ButtonView::class, $view['btn2']);
    }
}
