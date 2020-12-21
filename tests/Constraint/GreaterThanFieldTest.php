<?php

namespace Bdf\Form\Constraint;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use PHPUnit\Framework\TestCase;

class GreaterThanFieldTest extends TestCase
{
    /**
     *
     */
    public function test()
    {
        $form = new class extends CustomForm {
            protected function configure(FormBuilderInterface $builder): void
            {
                $builder->integer('a');
                $builder->integer('b')->depends('a')->satisfy(new GreaterThanField('a'));
            }
        };

        $form->submit(['a' => 1, 'b' => 2]);
        $this->assertTrue($form->valid());

        $form->submit(['a' => 2, 'b' => 2]);
        $this->assertFalse($form->valid());
        $this->assertEquals('This value should be greater than 2.', $form['b']->error()->global());
        $this->assertEquals('TOO_LOW_ERROR', $form['b']->error()->code());
    }
}
