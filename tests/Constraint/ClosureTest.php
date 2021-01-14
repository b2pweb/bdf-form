<?php

namespace Bdf\Form\Constraint;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use PHPUnit\Framework\TestCase;

class ClosureTest extends TestCase
{
    /**
     *
     */
    public function test_with_return_boolean_should_use_constraint_message()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->integer('a')->satisfy(new Closure(function ($value) {
                    return $value % 2 === 0;
                }));
            }
        };

        $form->submit(['a' => 8]);
        $this->assertTrue($form->valid());

        $form->submit(['a' => 3]);
        $this->assertFalse($form->valid());
        $this->assertEquals('The value is invalid', $form['a']->error()->global());
        $this->assertEquals('CUSTOM_ERROR', $form['a']->error()->code());
    }

    /**
     *
     */
    public function test_with_return_string_should_use_returned_value_as_error_message()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->integer('a')->satisfy(new Closure(function ($value) {
                    if ($value % 2 !== 0) {
                        return 'Value must be even';
                    }
                }));
            }
        };

        $form->submit(['a' => 8]);
        $this->assertTrue($form->valid());

        $form->submit(['a' => 3]);
        $this->assertFalse($form->valid());
        $this->assertEquals('Value must be even', $form['a']->error()->global());
        $this->assertEquals('CUSTOM_ERROR', $form['a']->error()->code());
    }

    /**
     *
     */
    public function test_with_return_array_should_use_returned_value_as_error_message_and_code()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->integer('a')->satisfy(new Closure(function ($value) {
                    if ($value % 2 !== 0) {
                        return [
                            'message' => 'Value must be even',
                            'code' => 'NOT_EVEN_ERROR',
                        ];
                    }
                }));
            }
        };

        $form->submit(['a' => 8]);
        $this->assertTrue($form->valid());

        $form->submit(['a' => 3]);
        $this->assertFalse($form->valid());
        $this->assertEquals('Value must be even', $form['a']->error()->global());
        $this->assertEquals('NOT_EVEN_ERROR', $form['a']->error()->code());
    }

    /**
     *
     */
    public function test_with_return_array_with_only_code_should_use_constraint_message()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->integer('a')->satisfy(new Closure(function ($value) {
                    if ($value % 2 !== 0) {
                        return [
                            'code' => 'NOT_EVEN_ERROR',
                        ];
                    }
                }));
            }
        };

        $form->submit(['a' => 8]);
        $this->assertTrue($form->valid());

        $form->submit(['a' => 3]);
        $this->assertFalse($form->valid());
        $this->assertEquals('The value is invalid', $form['a']->error()->global());
        $this->assertEquals('NOT_EVEN_ERROR', $form['a']->error()->code());
    }

    /**
     *
     */
    public function test_with_return_array_with_only_message_should_use_default_code()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->integer('a')->satisfy(new Closure(function ($value) {
                    if ($value % 2 !== 0) {
                        return [
                            'message' => 'Value must be even',
                        ];
                    }
                }));
            }
        };

        $form->submit(['a' => 8]);
        $this->assertTrue($form->valid());

        $form->submit(['a' => 3]);
        $this->assertFalse($form->valid());
        $this->assertEquals('Value must be even', $form['a']->error()->global());
        $this->assertEquals('CUSTOM_ERROR', $form['a']->error()->code());
    }
}
