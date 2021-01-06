<?php

namespace Bdf\Form\Util;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use PHPUnit\Framework\TestCase;

/**
 * Class FieldPathTest
 */
class FieldPathTest extends TestCase
{
    /**
     *
     */
    public function test_parse()
    {
        $this->assertEquals(new FieldPath(['..', 'firstName'], false), FieldPath::parse('firstName'));
        $this->assertEquals(new FieldPath(['..', 'firstName'], false), FieldPath::parse('../firstName'));
        $this->assertEquals(new FieldPath([], false), FieldPath::parse('.'));
        $this->assertEquals(new FieldPath(['..', 'person', 'firstName'], false), FieldPath::parse('person/firstName'));
        $this->assertEquals(new FieldPath(['foo', 'bar'], true), FieldPath::parse('/foo/bar'));
        $this->assertSame(FieldPath::parse('firstName'), FieldPath::parse('firstName'));
    }

    /**
     *
     */
    public function test_resolve()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->embedded('credentials', function ($builder) {
                    $builder->string('username');
                    $builder->string('password');
                });

                $builder->embedded('person', function ($builder) {
                    $builder->string('firstName');
                    $builder->string('lastName');
                });

                $builder->string('email');
            }
        };

        $field = $form['person']->element()['lastName']->element();

        $this->assertSame($field, FieldPath::parse('.')->resolve($field));
        $this->assertSame($form['person']->element()['firstName']->element(), FieldPath::parse('firstName')->resolve($field));
        $this->assertSame($form['email']->element(), FieldPath::parse('../../email')->resolve($field));
        $this->assertSame($form['credentials']->element()['username']->element(), FieldPath::parse('/credentials/username')->resolve($field));
        $this->assertSame($form['credentials']->element()['username']->element(), FieldPath::parse('/credentials/username')->resolve($field->container()));

        $this->assertNull(FieldPath::parse('notFound')->resolve($field));
        $this->assertNull(FieldPath::parse('../../..')->resolve($field));
    }

    /**
     *
     */
    public function test_value()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->embedded('credentials', function ($builder) {
                    $builder->string('username');
                    $builder->string('password');
                });

                $builder->embedded('person', function ($builder) {
                    $builder->string('firstName');
                    $builder->string('lastName');
                });

                $builder->string('email');
            }
        };

        $form->submit([
            'credentials' => [
                'username' => 'admin',
                'password' => 'nimda'
            ],
            'person' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'email' => 'j.doe@example.com',
        ]);

        $field = $form['person']->element()['lastName']->element();

        $this->assertSame('Doe', FieldPath::parse('.')->value($field));
        $this->assertSame('John', FieldPath::parse('firstName')->value($field));
        $this->assertSame('j.doe@example.com', FieldPath::parse('../../email')->value($field));
        $this->assertSame('admin', FieldPath::parse('/credentials/username')->value($field));

        $this->assertNull(FieldPath::parse('notFound')->value($field));
    }
}
