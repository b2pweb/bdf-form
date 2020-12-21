<?php

namespace Bdf\Form\Constraint;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use PHPUnit\Framework\TestCase;

class LessThanOrEqualFieldTest extends TestCase
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
                $builder->integer('b')->depends('a')->satisfy(new LessThanOrEqualField('a'));
            }
        };

        $form->submit(['a' => 1, 'b' => 2]);
        $this->assertFalse($form->valid());
        $this->assertEquals('This value should be less than or equal to 1.', $form['b']->error()->global());
        $this->assertEquals('TOO_HIGH_ERROR', $form['b']->error()->code());

        $form->submit(['a' => 2, 'b' => 2]);
        $this->assertTrue($form->valid());
        $this->assertTrue($form->submit(['a' => 3, 'b' => 2])->valid());
    }
}
