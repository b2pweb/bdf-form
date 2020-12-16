<?php

namespace Bdf\Form\Choice;

use PHPUnit\Framework\TestCase;

class ArrayChoiceTest extends TestCase
{
    /**
     *
     */
    public function test_default_construct()
    {
        $choice = new ArrayChoice(['label' => 'value']);

        $this->assertSame(['label' => 'value'], $choice->values());
    }

    /**
     *
     */
    public function test_view_value_without_transformers()
    {
        $choice = new ArrayChoice([
            'true'  => true,
            'false' => false,
            'float' => 2.1,
        ]);

        $values = $choice->view();

        $this->assertSame(true, $values[0]->value());
        $this->assertSame(false, $values[1]->value());
        $this->assertSame(2.1, $values[2]->value());
    }

    /**
     *
     */
    public function test_view_value_with_transformers()
    {
        $choice = new ArrayChoice([
            'float' => 2.1,
        ]);

        $values = $choice->view(function (ChoiceView $view) {
            $view->setValue(str_replace('.', ',', $view->value()));
            $view->setSelected(true);
        });

        $this->assertSame('2,1', $values[0]->value());
        $this->assertTrue($values[0]->selected());
    }
}
