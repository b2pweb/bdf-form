<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\View\FormView;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
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
    public function test_attach()
    {
        $person = new Person();
        $person->firstName = 'John';
        $person->lastName = 'Doe';
        $person->birthDate = new \DateTime('1992-05-22');

        $this->assertSame($this->form, $this->form->attach($person));
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
}
