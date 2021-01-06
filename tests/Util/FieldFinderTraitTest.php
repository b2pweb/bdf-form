<?php

namespace Bdf\Form\Util;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use PHPUnit\Framework\TestCase;

class FieldFinderTraitTest extends TestCase
{
    /**
     *
     */
    public function test_findField()
    {
        $form = new CredentialsForm();

        $this->assertSame($form['username']->element(), $form->findField('username'));
        $this->assertSame($form['password']->element(), $form->findField('password'));
    }

    /**
     *
     */
    public function test_findField_with_embedded()
    {
        $builder = new FormBuilder();
        $builder->add('credentials', CredentialsForm::class);
        $builder->string('foo');

        $form = $builder->buildElement();

        /** @var CredentialsForm $credentials */
        $credentials = $form['credentials']->element();

        $this->assertSame($credentials['username']->element(), $credentials->findField('username'));
        $this->assertSame($form['foo']->element(), $credentials->findField('../foo'));
        $this->assertSame($form['foo']->element(), $credentials->findField('/foo'));
    }
    /**
     *
     */
    public function test_findFieldValue()
    {
        $form = new CredentialsForm();

        $form->submit(['username' => 'foo', 'password' => 'bar']);

        $this->assertSame('foo', $form->findFieldValue('username'));
        $this->assertSame('bar', $form->findFieldValue('password'));
    }

    /**
     *
     */
    public function test_findFieldValue_with_embedded()
    {
        $builder = new FormBuilder();
        $builder->add('credentials', CredentialsForm::class);
        $builder->string('foo');

        $form = $builder->buildElement();

        $form->submit([
            'foo' => 'bar',
            'credentials' => [
                'username' => 'admin',
                'password' => '123admin',
            ]
        ]);

        /** @var CredentialsForm $credentials */
        $credentials = $form['credentials']->element();

        $this->assertSame('admin', $credentials->findFieldValue('username'));
        $this->assertSame('bar', $credentials->findFieldValue('../foo'));
        $this->assertSame('bar', $credentials->findFieldValue('/foo'));
    }
}

class CredentialsForm extends CustomForm
{
    use FieldFinderTrait;

    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->string('username')->required();
        $builder->string('password')->required();
    }
}
