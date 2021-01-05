<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\View\FormView;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\Registry\Registry;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Validator\Constraints\Closure;
use PHPUnit\Framework\TestCase;

/**
 * Class FunctionalTest
 */
class FormTest extends TestCase
{
    /**
     * @var Form
     */
    private $form;

    /**
     * @var Registry
     */
    private $registry;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->registry = new Registry();

        $this->form = new Form(new ChildrenCollection([
            $this->registry->childBuilder(StringElement::class, 'firstName')->getter()->setter()->length(['min' => 2])->buildChild(),
            $this->registry->childBuilder(StringElement::class, 'lastName')->getter()->setter()->length(['min' => 2])->buildChild(),
            $this->registry->childBuilder(IntegerElement::class, 'id')->getter()->setter()->buildChild(),
        ]));
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $this->form->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ]);

        $this->assertTrue($this->form->valid());
        $this->assertTrue($this->form->error()->empty());

        $this->assertSame('John', $this->form['firstName']->element()->value());
        $this->assertSame('Smith', $this->form['lastName']->element()->value());
        $this->assertSame(4, $this->form['id']->element()->value());
    }

    /**
     *
     */
    public function test_submit_error_on_child()
    {
        $this->form->submit([
            'firstName' => 'J',
            'lastName' => 'S',
            'id' => '4',
        ]);

        $this->assertFalse($this->form->valid());
        $this->assertFalse($this->form->error()->empty());
        $this->assertEquals([
            'firstName' => 'This value is too short. It should have 2 characters or more.',
            'lastName' => 'This value is too short. It should have 2 characters or more.',
        ], $this->form->error()->toArray());

        $this->assertSame('J', $this->form['firstName']->element()->value());
        $this->assertFalse($this->form['firstName']->element()->valid());
        $this->assertSame('S', $this->form['lastName']->element()->value());
        $this->assertFalse($this->form['lastName']->element()->valid());
        $this->assertSame(4, $this->form['id']->element()->value());
    }

    /**
     *
     */
    public function test_submit_error_on_form()
    {
        $form = new Form(new ChildrenCollection([
            $this->registry->childBuilder(StringElement::class, 'password')->length(['min' => 8])->buildChild(),
            $this->registry->childBuilder(StringElement::class, 'confirm')->buildChild(),
        ]), new ConstraintValueValidator(new Closure(function ($value, $form) {
            if ($form['password']->element()->value() != $form['confirm']->element()->value()) {
                return 'invalid confirmation';
            }
        })));

        $form->submit([
            'password' => 'hello world !',
            'confirm' => 'hello xorld !',
        ]);

        $this->assertFalse($form->valid());
        $this->assertFalse($form->error()->empty());
        $this->assertEquals(['invalid confirmation'], $form->error()->toArray());
    }

    /**
     *
     */
    public function test_submit_with_view_transformer()
    {
        $form = new Form(new ChildrenCollection([
            $this->registry->childBuilder(StringElement::class, 'firstName')->getter()->length(['min' => 2])->buildChild(),
            $this->registry->childBuilder(StringElement::class, 'lastName')->getter()->length(['min' => 2])->buildChild(),
            $this->registry->childBuilder(IntegerElement::class, 'id')->getter()->buildChild(),
        ]), null, new ClosureTransformer(function ($value) { return array_map('strtolower', $value); }));

        $form->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ]);

        $this->assertTrue($form->valid());
        $this->assertTrue($form->error()->empty());

        $this->assertSame('john', $form['firstName']->element()->value());
        $this->assertSame('smith', $form['lastName']->element()->value());
        $this->assertSame(4, $form['id']->element()->value());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $form = new Form(new ChildrenCollection([
            $this->registry->childBuilder(StringElement::class, 'firstName')->getter()->length(['min' => 2])->buildChild(),
            $this->registry->childBuilder(StringElement::class, 'lastName')->getter()->length(['min' => 2])->buildChild(),
            $this->registry->childBuilder(IntegerElement::class, 'id')->getter()->buildChild(),
        ]), null, new ClosureTransformer(function () { throw new \Exception('my error'); }));

        $form->import([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => 4,
        ]);

        $form->submit([]);

        $this->assertFalse($form->valid());
        $this->assertFalse($form->error()->empty());
        $this->assertEquals('my error', $form->error()->global());

        $this->assertNull($form['firstName']->element()->value());
        $this->assertNull($form['lastName']->element()->value());
        $this->assertNull($form['id']->element()->value());
    }

    /**
     *
     */
    public function test_import_and_patch_null()
    {
        $entity = new Person();

        $entity->id = 42;
        $entity->firstName = 'Mike';
        $entity->lastName = 'Smith';

        $this->assertEquals($entity, $this->form->import($entity)->value());
        $this->assertEquals($entity, $this->form->patch(null)->value());

        $this->assertSame('Mike', $this->form['firstName']->element()->value());
        $this->assertSame('Smith', $this->form['lastName']->element()->value());
        $this->assertSame(42, $this->form['id']->element()->value());
    }

    /**
     *
     */
    public function test_import_and_patch_partial()
    {
        $entity = new Person();

        $entity->id = 42;
        $entity->firstName = 'Mike';
        $entity->lastName = 'Smith';

        $this->form->import($entity);

        $expectedEntity = clone $entity;
        $expectedEntity->firstName = 'John';

        $this->assertEquals($expectedEntity, $this->form->patch(['firstName' => 'John'])->value());

        $this->assertSame('John', $this->form['firstName']->element()->value());
        $this->assertSame('Smith', $this->form['lastName']->element()->value());
        $this->assertSame(42, $this->form['id']->element()->value());
    }

    /**
     *
     */
    public function test_import_and_patch_with_error()
    {
        $entity = new Person();

        $entity->id = 42;
        $entity->firstName = 'Mike';
        $entity->lastName = 'Smith';

        $this->form->import($entity);

        $expectedEntity = clone $entity;
        $expectedEntity->firstName = 'J';

        $this->assertEquals($expectedEntity, $this->form->patch(['firstName' => 'J'])->value());

        $this->assertFalse($this->form->valid());
        $this->assertEquals('firstName : This value is too short. It should have 2 characters or more.', (string) $this->form->error());

        $this->assertSame('J', $this->form['firstName']->element()->value());
        $this->assertSame('Smith', $this->form['lastName']->element()->value());
        $this->assertSame(42, $this->form['id']->element()->value());
    }

    /**
     *
     */
    public function test_import_and_patch_with_sibling_element_error()
    {
        $builder = new FormBuilder($this->registry);

        $builder->string('firstName')
            ->satisfy(function ($value, ElementInterface $input) { if ($value == $input->container()->parent()['lastName']->element()->value()) { return 'my error'; } })
            ->depends('lastName')
            ->getter()
            ->setter()
        ;

        $builder->string('lastName')->getter()->setter();
        $builder->integer('id')->getter()->setter();

        $form = $builder->buildElement();

        $entity = new Person();

        $entity->id = 42;
        $entity->firstName = 'Mike';
        $entity->lastName = 'Smith';

        $form->import($entity);

        $expectedEntity = clone $entity;
        $expectedEntity->lastName = 'Mike';

        $this->assertEquals($expectedEntity, $form->patch(['lastName' => 'Mike'])->value());

        $this->assertFalse($form->valid());
        $this->assertEquals('firstName : my error', (string) $form->error());

        $this->assertSame('Mike', $form['firstName']->element()->value());
        $this->assertSame('Mike', $form['lastName']->element()->value());
        $this->assertSame(42, $form['id']->element()->value());
    }

    /**
     *
     */
    public function test_patch_success()
    {
        $this->form->patch([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ]);

        $this->assertTrue($this->form->valid());
        $this->assertTrue($this->form->error()->empty());

        $this->assertSame('John', $this->form['firstName']->element()->value());
        $this->assertSame('Smith', $this->form['lastName']->element()->value());
        $this->assertSame(4, $this->form['id']->element()->value());
    }

    /**
     *
     */
    public function test_import_with_entity()
    {
        $form = new Form(new ChildrenCollection([
            $this->registry->childBuilder(StringElement::class, 'firstName')->getter()->buildChild(),
            $this->registry->childBuilder(StringElement::class, 'lastName')->getter()->buildChild(),
            $this->registry->childBuilder(IntegerElement::class, 'id')->getter()->buildChild(),
        ]));

        $entity = new Person();

        $entity->id = 42;
        $entity->firstName = 'Mike';
        $entity->lastName = 'Smith';

        $this->assertEquals($entity, $form->import($entity)->value());

        $this->assertSame('Mike', $form['firstName']->element()->value());
        $this->assertSame('Smith', $form['lastName']->element()->value());
        $this->assertSame(42, $form['id']->element()->value());
    }

    /**
     *
     */
    public function test_import_with_array()
    {
        $form = new Form(new ChildrenCollection([
            $this->registry->childBuilder(StringElement::class, 'firstName')->getter()->buildChild(),
            $this->registry->childBuilder(StringElement::class, 'lastName')->getter()->buildChild(),
            $this->registry->childBuilder(IntegerElement::class, 'id')->getter()->buildChild(),
        ]));

        $form->import($data = [
            'id' => 42,
            'firstName' => 'Mike',
            'lastName' => 'Smith',
        ]);

        $this->assertSame($data, $form->import($data)->value());

        $this->assertSame('Mike', $form['firstName']->element()->value());
        $this->assertSame('Smith', $form['lastName']->element()->value());
        $this->assertSame(42, $form['id']->element()->value());
    }

    /**
     *
     */
    public function test_value_empty()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->setter()->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->setter()->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->setter()->buildChild(),
        ]));

        $this->assertSame([
            'firstName' => null,
            'lastName' => null,
            'id' => null,
        ], $form->value());
    }

    /**
     *
     */
    public function test_value_array()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->setter()->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->setter()->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->setter()->buildChild(),
        ]));

        $form->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ]);

        $this->assertSame([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => 4,
        ], $form->value());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]));

        $form->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ]);

        $this->assertSame([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ], $form->httpValue());
    }

    /**
     *
     */
    public function test_httpValue_with_view_transformer()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]), null, new ClosureTransformer(function ($value) {
            return array_map('strtoupper', $value);
        }));

        $form->submit([
            'firstName' => 'John',
            'lastName' => 'Smith',
            'id' => '4',
        ]);

        $this->assertSame([
            'firstName' => 'JOHN',
            'lastName' => 'SMITH',
            'id' => '4',
        ], $form->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $form = new Form(new ChildrenCollection());

        $this->assertNull($form->container());

        $container = $this->createMock(ChildInterface::class);
        $newForm = $form->setContainer($container);

        $this->assertSame($container, $newForm->container());
        $this->assertNull($form->container());
    }

    /**
     *
     */
    public function test_root()
    {
        $form = new Form(new ChildrenCollection());

        $this->assertInstanceOf(RootForm::class, $form->root());

        $parent = new Form(new ChildrenCollection([
            $container = new Child('sub', $form)
        ]));

        $this->assertSame($parent->root(), $container->element()->root());
    }

    /**
     *
     */
    public function test_spl_access()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]));

        $this->assertArrayHasKey('firstName', $form);
        $this->assertArrayHasKey('lastName', $form);
        $this->assertArrayHasKey('id', $form);

        $this->assertInstanceOf(Child::class, $form['firstName']);
        $this->assertInstanceOf(StringElement::class, $form['firstName']->element());
        $this->assertInstanceOf(Child::class, $form['lastName']);
        $this->assertInstanceOf(StringElement::class, $form['lastName']->element());
        $this->assertInstanceOf(Child::class, $form['id']);
        $this->assertInstanceOf(IntegerElement::class, $form['id']->element());

        $this->assertSame([
            'firstName' => $form['firstName'],
            'lastName' => $form['lastName'],
            'id' => $form['id'],
        ], iterator_to_array($form));
    }

    /**
     *
     */
    public function test_clone()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]));

        $cloned = clone $form;

        $this->assertEquals($cloned, $form);

        $this->assertNotSame($form['firstName'], $cloned['firstName']);
        $this->assertSame($cloned, $cloned['firstName']->parent());
    }

    /**
     *
     */
    public function test_submit_with_dependencies()
    {
        $builder = new FormBuilder();
        $builder->integer('a');
        $builder->integer('c')->satisfy(function ($value, $element) {
            $parent = $element->container()->parent();

            if ($value !== $parent['a']->element()->value() + $parent['b']->element()->value()) {
                return 'a+b != c';
            }
        });
        $builder->integer('b');

        $form = $builder->buildElement();

        $this->assertFalse($form->submit(['a' => '4', 'b' => '3', 'c' => '6'])->valid());
        $this->assertTrue($form->submit(['a' => '4', 'b' => '3', 'c' => '7'])->valid());
    }

    /**
     *
     */
    public function test_attach()
    {
        $registry = new Registry();

        $form = new Form(new ChildrenCollection([
            $registry->childBuilder(StringElement::class, 'firstName')->setter()->buildChild(),
            $registry->childBuilder(StringElement::class, 'lastName')->setter()->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->setter()->buildChild(),
        ]));

        $person = $form
            ->attach(Person::class)
            ->submit([
                'firstName' => 'John',
                'lastName' => 'Smith',
                'id' => '4',
            ])
            ->value()
        ;

        $this->assertInstanceOf(Person::class, $person);
        $this->assertEquals(4, $person->id);
        $this->assertEquals('John', $person->firstName);
        $this->assertEquals('Smith', $person->lastName);
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

        /** @var FormView $view */
        $view = $form->view();

        $this->assertInstanceOf(FormView::class, $view);
        $this->assertInstanceOf(SimpleElementView::class, $view['firstName']);
        $this->assertInstanceOf(SimpleElementView::class, $view['lastName']);
        $this->assertInstanceOf(SimpleElementView::class, $view['id']);

        $this->assertNull($view->error());
        $this->assertFalse($view->hasError());
        $this->assertEquals(Form::class, $view->type());

        $this->assertTrue(isset($view['firstName']));
        $this->assertTrue(isset($view['lastName']));
        $this->assertTrue(isset($view['id']));

        $this->assertEquals('<input type="text" name="firstName" value="" />', (string) $view['firstName']);
        $this->assertEquals('<input type="text" name="lastName" value="" />', (string) $view['lastName']);
        $this->assertEquals('<input type="number" name="id" value="" />', (string) $view['id']);

        $form->submit([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'id' => '145',
        ]);

        $view = $form->view();

        $this->assertEquals('<input type="text" name="firstName" value="John" />', (string) $view['firstName']);
        $this->assertEquals('<input type="text" name="lastName" value="Doe" />', (string) $view['lastName']);
        $this->assertEquals('<input type="number" name="id" value="145" />', (string) $view['id']);
    }

    /**
     *
     */
    public function test_view_with_embedded_prefix()
    {
        $registry = new Registry();

        $nameFormBuilder = $registry->childBuilder(Form::class, 'name');
        $nameFormBuilder->prefix();
        $nameFormBuilder->string('first');
        $nameFormBuilder->string('last');

        $form = new Form(new ChildrenCollection([
            $nameFormBuilder->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]));

        /** @var FormView $view */
        $view = $form->view();

        $this->assertInstanceOf(FormView::class, $view);
        $this->assertInstanceOf(FormView::class, $view['name']);
        $this->assertInstanceOf(SimpleElementView::class, $view['name']['first']);
        $this->assertInstanceOf(SimpleElementView::class, $view['name']['last']);
        $this->assertInstanceOf(SimpleElementView::class, $view['id']);

        $this->assertNull($view->error());
        $this->assertFalse($view->hasError());
        $this->assertEquals(Form::class, $view->type());

        $this->assertEquals('<input type="text" name="name_first" value="" />', (string) $view['name']['first']);
        $this->assertEquals('<input type="text" name="name_last" value="" />', (string) $view['name']['last']);
        $this->assertEquals('<input type="number" name="id" value="" />', (string) $view['id']);
    }

    /**
     *
     */
    public function test_view_with_embedded_array()
    {
        $registry = new Registry();

        $nameFormBuilder = $registry->childBuilder(Form::class, 'name');
        $nameFormBuilder->string('first');
        $nameFormBuilder->string('last');

        $form = new Form(new ChildrenCollection([
            $nameFormBuilder->buildChild(),
            $registry->childBuilder(IntegerElement::class, 'id')->buildChild(),
        ]));

        /** @var FormView $view */
        $view = $form->view();

        $this->assertInstanceOf(FormView::class, $view);
        $this->assertInstanceOf(FormView::class, $view['name']);
        $this->assertInstanceOf(SimpleElementView::class, $view['name']['first']);
        $this->assertInstanceOf(SimpleElementView::class, $view['name']['last']);
        $this->assertInstanceOf(SimpleElementView::class, $view['id']);

        $this->assertNull($view->error());
        $this->assertFalse($view->hasError());
        $this->assertEquals(Form::class, $view->type());

        $this->assertEquals('<input type="text" name="name[first]" value="" />', (string) $view['name']['first']);
        $this->assertEquals('<input type="text" name="name[last]" value="" />', (string) $view['name']['last']);
        $this->assertEquals('<input type="number" name="id" value="" />', (string) $view['id']);
    }
}

class Person
{
    public $id;
    public $firstName;
    public $lastName;
}
