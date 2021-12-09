<?php

namespace Bdf\Form\Choice;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class LazyChoiceTest extends TestCase
{
    /**
     *
     */
    public function test_default_construct()
    {
        $choice = new LazyChoice(function() {
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

        $choice = new LazyChoice(function() use($mock) {
            return $mock;
        });

        $choice->values();
        $choice->view();
    }
}
