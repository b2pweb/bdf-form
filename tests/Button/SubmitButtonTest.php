<?php

namespace Bdf\Form\Button;

use PHPUnit\Framework\TestCase;

/**
 * Class SubmitButtonTest
 */
class SubmitButtonTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $btn = new SubmitButton('btn', 'aaa', ['grp']);

        $this->assertEquals('btn', $btn->name());
        $this->assertEquals(['grp'], $btn->constraintGroups());
        $this->assertFalse($btn->clicked());
    }

    /**
     *
     */
    public function test_submit()
    {
        $btn = new SubmitButton('btn', 'aaa');

        $this->assertFalse($btn->submit(null));
        $this->assertFalse($btn->submit([]));
        $this->assertFalse($btn->submit(['btn' => 'bbb']));
        $this->assertFalse($btn->submit(['other' => 'aaa']));
        $this->assertFalse($btn->clicked());

        $this->assertTrue($btn->submit(['btn' => 'aaa']));
        $this->assertTrue($btn->clicked());
    }
}
