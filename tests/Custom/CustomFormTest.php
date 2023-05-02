<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\FooForm;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Aggregate\RootForm;
use Bdf\Form\Aggregate\View\FormView;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Error\FormErrorPrinterInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Util\FieldFinderTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\LessThan;

/**
 * Class CustomFormTest
 */
class CustomFormTest extends TestCase
{
    /**
     * @var PersonForm
     */
    private $form;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->form = new PersonForm();
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $this->assertSame($this->form, $this->form->submit(['firstName' => 'John', 'lastName' => 'Doe', 'birthDate' => (new \DateTime('1992-05-22'))->getTimestamp()]));
        $this->assertTrue($this->form->valid());

        $person = $this->form->value();

        $this->assertInstanceOf(Person::class, $person);
        $this->assertSame('John', $person->firstName);
        $this->assertSame('Doe', $person->lastName);
        $this->assertEquals(new \DateTime('1992-05-22'), $person->birthDate);

        $this->assertContainsOnlyInstancesOf(Child::class, $this->form);
        $this->assertArrayHasKey('firstName', $this->form);
        $this->assertArrayHasKey('lastName', $this->form);
        $this->assertArrayHasKey('birthDate', $this->form);
    }

    /**
     *
     */
    public function test_submit_on_embedded()
    {
        $parentForm = new class() extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->add('embedded', PersonForm::class)->setter();
                $builder->string('foo')->setter();
            }
        };

        $this->assertSame($parentForm, $parentForm->submit([
            'embedded' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'birthDate' => (new \DateTime('1992-05-22'))->getTimestamp(),
            ],
            'foo' => 'bar',
        ]));
        $this->assertTrue($parentForm->valid());

        $value = $parentForm->value();

        $this->assertInstanceOf(Person::class, $value['embedded']);
        $this->assertSame('John', $value['embedded']->firstName);
        $this->assertSame('Doe', $value['embedded']->lastName);
        $this->assertEquals(new \DateTime('1992-05-22'), $value['embedded']->birthDate);
        $this->assertSame('bar', $value['foo']);
    }

    /**
     *
     */
    public function test_submit_with_button()
    {
        $this->form = new PersonFormWithButton();
        $this->assertSame($this->form, $this->form->submit([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'birthDate' => (new \DateTime('1992-05-22'))->getTimestamp(),
            'btn' => 'ok',
        ]));
        $this->assertTrue($this->form->valid());

        $person = $this->form->value();

        $this->assertInstanceOf(Person::class, $person);
        $this->assertSame('John', $person->firstName);
        $this->assertSame('Doe', $person->lastName);
        $this->assertEquals(new \DateTime('1992-05-22'), $person->birthDate);
        $this->assertEquals('btn', $this->form->root()->submitButton()->name());
    }

    /**
     *
     */
    public function test_submit_error()
    {
        $this->assertSame($this->form, $this->form->submit(['firstName' => 'John']));
        $this->assertFalse($this->form->valid());
        $this->assertEquals(['lastName' => 'This value should not be blank.'], $this->form->error()->toArray());
    }

    /**
     *
     */
    public function test_patch_success()
    {
        $person = new Person();
        $person->firstName = 'John';
        $person->lastName = 'Doe';
        $person->birthDate = new \DateTime('1992-05-22');

        $this->form->import($person);

        $this->assertSame($this->form, $this->form->patch(['firstName' => 'Paul']));
        $this->assertTrue($this->form->valid());

        $person = $this->form->value();

        $this->assertInstanceOf(Person::class, $person);
        $this->assertSame('Paul', $person->firstName);
        $this->assertSame('Doe', $person->lastName);
        $this->assertEquals(new \DateTime('1992-05-22'), $person->birthDate);
    }

    /**
     *
     */
    public function test_patch_on_embedded()
    {
        $parentForm = new class() extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->add('embedded', PersonForm::class)->setter();
                $builder->string('foo')->setter();
            }
        };

        $parentForm->submit([
            'embedded' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'birthDate' => (new \DateTime('1992-05-22'))->getTimestamp(),
            ],
            'foo' => 'bar',
        ]);

        $parentForm->patch(['embedded' => ['firstName' => 'Paul']]);

        $value = $parentForm->value();

        $this->assertInstanceOf(Person::class, $value['embedded']);
        $this->assertSame('Paul', $value['embedded']->firstName);
        $this->assertSame('Doe', $value['embedded']->lastName);
        $this->assertEquals(new \DateTime('1992-05-22'), $value['embedded']->birthDate);
        $this->assertSame('bar', $value['foo']);
    }

    /**
     *
     */
    public function test_import()
    {
        $person = new Person();
        $person->firstName = 'John';
        $person->lastName = 'Doe';
        $person->birthDate = new \DateTime('1992-05-22');

        $this->assertSame($this->form, $this->form->import($person));
        $this->assertEquals($person, $this->form->value());

        $this->assertSame('John', $this->form['firstName']->element()->value());
        $this->assertSame('Doe', $this->form['lastName']->element()->value());
        $this->assertSame($person->birthDate->getTimestamp(), $this->form['birthDate']->element()->value());

        $this->assertSame([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'birthDate' => (string) $person->birthDate->getTimestamp(),
        ], $this->form->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $container = new Child('child', $this->form);
        $form = $this->form->setContainer($container);

        $this->assertNotSame($form, $this->form);
        $this->assertSame($container, $form->container());
        $this->assertNull($this->form->container());
    }

    /**
     *
     */
    public function test_functional_array_of_custom_form()
    {
        $array = new ArrayElement(new PersonForm());

        $array->submit([
            ['firstName' => 'Mickey', 'lastName' => 'Mouse'],
            ['firstName' => 'Minnie', 'lastName' => 'Mouse'],
        ]);

        $this->assertTrue($array->valid());
        $this->assertCount(2, $array);

        $this->assertContainsOnly(Person::class, $array->value());
        $this->assertSame('Mickey', $array->value()[0]->firstName);
        $this->assertSame('Mouse', $array->value()[0]->lastName);
        $this->assertSame('Minnie', $array->value()[1]->firstName);
        $this->assertSame('Mouse', $array->value()[1]->lastName);
    }

    /**
     *
     */
    public function test_function_complex_form_error_and_view()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->string('foo')->required();
                $builder->array('arr')->form(function (FormBuilder $builder) {
                    $builder->embedded('emb', function ($builder) {
                        $builder->string('aaa')->required();
                        $builder->string('bbb')->required();
                    })->prefix();

                    $builder->embedded('other', function ($builder) {
                        $builder->string('aaa')->required();
                        $builder->string('bbb')->required();
                    });
                });
            }
        };

        $form->submit([
            'arr' => [['']],
        ]);

        $errors = $form->error()->print(new class implements FormErrorPrinterInterface {
            private $errors = [];
            private $global;
            private $code;
            private $field;

            public function global(string $error): void
            {
                $this->global = $error;
            }

            public function code(string $code): void
            {
                $this->code = $code;
            }

            public function field(HttpFieldPath $field): void
            {
                $this->field = $field;
            }

            public function child(string $name, FormError $error): void
            {
                $error->print($this);
            }

            public function print()
            {
                if ($this->global || $this->code) {
                    $this->errors[] = [
                        'field' => $this->field->get(),
                        'message' => $this->global,
                        'code' => $this->code,
                    ];

                    $this->global = null;
                    $this->field = null;
                    $this->code = null;
                }

                return $this->errors;
            }
        });

        $this->assertEquals([
            [
                'field' => 'foo',
                'message' => 'This value should not be blank.',
                'code' => 'IS_BLANK_ERROR',
            ],
            [
                'field' => 'arr[0][emb_aaa]',
                'message' => 'This value should not be blank.',
                'code' => 'IS_BLANK_ERROR',
            ],
            [
                'field' => 'arr[0][emb_bbb]',
                'message' => 'This value should not be blank.',
                'code' => 'IS_BLANK_ERROR',
            ],
            [
                'field' => 'arr[0][other][aaa]',
                'message' => 'This value should not be blank.',
                'code' => 'IS_BLANK_ERROR',
            ],
            [
                'field' => 'arr[0][other][bbb]',
                'message' => 'This value should not be blank.',
                'code' => 'IS_BLANK_ERROR',
            ],
        ], $errors);

        $view = $form->view();

        $this->assertEquals('<input type="text" name="foo" value="" required />', (string) $view['foo']);
        $this->assertEquals('<input type="text" name="arr[0][emb_aaa]" value="" required />', (string) $view['arr'][0]['emb']['aaa']);
        $this->assertEquals('<input type="text" name="arr[0][emb_bbb]" value="" required />', (string) $view['arr'][0]['emb']['bbb']);
        $this->assertEquals('<input type="text" name="arr[0][other][aaa]" value="" required />', (string) $view['arr'][0]['other']['aaa']);
        $this->assertEquals('<input type="text" name="arr[0][other][bbb]" value="" required />', (string) $view['arr'][0]['other']['bbb']);
    }

    /**
     *
     */
    public function test_attach()
    {
        $person = new Person();
        $person->firstName = 'John';
        $person->lastName = 'Doe';
        $person->birthDate = new \DateTime('1992-05-22');
        $person->notDeclaredOnForm = 'foo';

        $this->assertSame($this->form, $this->form->attach($person));
        $this->assertSame('foo', $this->form->value()->notDeclaredOnForm);
        $this->assertNull($this->form['firstName']->element()->value());
        $this->assertNull($this->form['lastName']->element()->value());
        $this->assertNull($this->form['birthDate']->element()->value());
    }

    /**
     *
     */
    public function test_view()
    {
        $view = $this->form->view();

        $this->assertInstanceOf(FormView::class, $view);
        $this->assertEquals(PersonForm::class, $view->type());
        $this->assertFalse($view->hasError());

        $this->assertEquals(StringElement::class, $view['firstName']->type());
        $this->assertEquals('<input type="text" name="firstName" value="" required />', (string) $view['firstName']);
        $this->assertEquals(StringElement::class, $view['lastName']->type());
        $this->assertEquals('<input type="text" name="lastName" value="" required />', (string) $view['lastName']);
        $this->assertEquals(IntegerElement::class, $view['birthDate']->type());
        $this->assertEquals('<input type="number" name="birthDate" value="" />', (string) $view['birthDate']);

        $view = $this->form->submit(['firstName' => 'John', 'lastName' => 'Doe', 'birthDate' => $date = time() + 100])->view();

        $this->assertTrue($view->hasError());

        $this->assertEquals('<input type="text" name="firstName" value="John" required />', (string) $view['firstName']);
        $this->assertEquals('<input type="text" name="lastName" value="Doe" required />', (string) $view['lastName']);
        $this->assertEquals('<input type="number" name="birthDate" value="'.$date.'" />', (string) $view['birthDate']);
        $this->assertStringStartsWith('This value should be less than', $view['birthDate']->error());
    }

    /**
     *
     */
    public function test_view_with_prefix()
    {
        $view = $this->form->view(HttpFieldPath::prefixed('foo_'));

        $this->assertEquals('<input type="text" name="foo_firstName" value="" required />', (string) $view['firstName']);
        $this->assertEquals('<input type="text" name="foo_lastName" value="" required />', (string) $view['lastName']);
        $this->assertEquals('<input type="number" name="foo_birthDate" value="" />', (string) $view['birthDate']);
    }

    /**
     *
     */
    public function test_view_with_btn()
    {
        $this->form = new PersonFormWithButton();
        $view = $this->form->view();

        $this->assertInstanceOf(FormView::class, $view);
        $this->assertEquals(PersonFormWithButton::class, $view->type());
        $this->assertFalse($view->hasError());

        $this->assertEquals('<input type="text" name="firstName" value="" required />', (string) $view['firstName']);
        $this->assertEquals('<input type="text" name="lastName" value="" required />', (string) $view['lastName']);
        $this->assertEquals('<input type="number" name="birthDate" value="" />', (string) $view['birthDate']);
        $this->assertEquals('<input type="submit" name="btn" value="ok" />', (string) $view['btn']);
    }

    /**
     *
     */
    public function test_protected_visibility()
    {
        $this->form = new class extends PersonForm {
            public function test(): void
            {
                TestCase::assertInstanceOf(Form::class, $this->form());
                TestCase::assertInstanceOf(RootForm::class, $this->submitTarget());
            }
        };

        $this->form->test();
    }

    /**
     *
     */
    public function test_postConfigure()
    {
        $form = new class extends CustomForm {
            public $param;

            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->string('foo');
            }

            public function postConfigure(FormInterface $form): void
            {
                $this->param = $form;
            }
        };

        $form->submit([]); // Build the form

        $this->assertInstanceOf(Form::class, $form->param);
        $this->assertInstanceOf(StringElement::class, $form->param['foo']->element());
    }

    public function test_sibling_into_an_embedded_custom_form()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->add('foo', EmbeddedWithFieldFinderForm::class);
            }
        };

        $form->submit(['foo' => ['bar' => 'abc', 'baz' => ['a', 'b', 'c']]]);

        $this->assertTrue($form->valid());

        $form->submit(['foo' => ['bar' => 'abcd', 'baz' => ['a', 'b', 'c']]]);
        $this->assertFalse($form->valid());
    }

    public function test_setContainer_should_rebuild_form()
    {
        $form = new class extends CustomForm {
            public $value = 'foo';

            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->string('foo')->setter(function () {
                    return $this->value;
                });
            }
        };

        $form->value = 'bar';
        $this->assertEquals('bar', $form->submit([])->value()['foo']);

        $parent = new Form(new ChildrenCollection());
        $child = new Child('a', $form);
        $child->setParent($parent);

        $newForm = $form->setContainer($child);
        $newForm->value = 'baz';
        $this->assertEquals('bar', $form->submit([])->value()['foo']);
        $this->assertEquals('baz', $newForm->submit([])->value()['foo']);
    }

    public function test_optional_embedded()
    {
        $form = new class extends CustomForm {
            public $param;

            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->add('person', PersonForm::class)
                    ->setter()
                    ->optional()
                ;
            }
        };

        $this->assertTrue($form->submit([])->valid());
        $this->assertTrue($form->submit(['person' => []])->valid());
        $this->assertSame(['person' => null], $form->value());

        $this->assertArrayHasKey('firstName', $form['person']->element());
        $this->assertArrayHasKey('lastName', $form['person']->element());
        $this->assertArrayHasKey('birthDate', $form['person']->element());

        $this->assertFalse($form->submit(['person' => ['firstName' => 'John']])->valid());
    }
}

/**
 * @method Person value()
 */
class PersonForm extends CustomForm
{
    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->generates(Person::class);

        $builder->string('firstName')->required()->getter()->setter();
        $builder->string('lastName')->required()->getter()->setter();
        $builder->integer('birthDate')
            ->raw()
            ->satisfy(new LessThan(time()))
            ->getter(function (?\DateTime $value) {
                return $value ? $value->getTimestamp() : null;
            })
            ->setter(function ($value) {
                return $value === null ? null : new \DateTime('@'.$value);
            })
        ;
    }
}

class PersonFormWithButton extends PersonForm
{
    protected function configure(FormBuilderInterface $builder): void
    {
        parent::configure($builder);

        $builder->submit('btn');
    }
}

class Person
{
    public $firstName;
    public $lastName;
    public $birthDate;

    public $notDeclaredOnForm;
}

class EmbeddedWithFieldFinderForm extends CustomForm
{
    use FieldFinderTrait;

    protected function configure(FormBuilderInterface $builder): void
    {
        $builder
            ->array('baz')
            ->depends('bar')
            ->arrayConstraint(function ($value) {
                $bar = $this->findFieldValue('bar');

                return count($value) === strlen($bar);
            })
        ;
        $builder->string('bar');
    }
}
