<?php

namespace Bdf\Form\Choice;

use PHPUnit\Framework\TestCase;

/**
 * @group Bdf
 * @group Bdf_Form
 * @group Bdf_Form_Choice
 * @group Bdf_Form_Choice_LazzyChoice
 */
class LazzyChoiceTest extends TestCase
{
    /**
     *
     */
    public function test_default_construct()
    {
        $choice = new LazzyChoice(function() {
            return ['label' => 'value'];
        });

        $this->assertSame(['label' => 'value'], $choice->values());
    }

    /**
     *
     */
    public function test_delegate()
    {
        $mock = $this->createMock(ChoiceInterface::class);
        $mock->expects($this->once())->method('values');
        $mock->expects($this->once())->method('view');

        $choice = new LazzyChoice(function() use($mock) {
            return $mock;
        });

        $choice->values();
        $choice->view();
    }
}
