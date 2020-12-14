<?php

namespace Bdf\Form\Button;

use Bdf\Form\Child\Http\HttpFieldPath;
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

    /**
     *
     */
    public function test_view()
    {
        $btn = new SubmitButton('btn', 'ok');

        $view = $btn->view();

        $this->assertEquals('btn', $view->name());
        $this->assertEquals('ok', $view->value());
        $this->assertFalse($view->clicked());
        $this->assertEquals('<input type="submit" name="btn" value="ok" />', (string) $view);
        $this->assertEquals('<button class="btn btn-primary" type="submit" name="btn" value="ok">My button</button>', (string) $view->class('btn btn-primary')->inner('My button'));

        $btn->submit(['btn' => 'ok']);
        $view = $btn->view();
        $this->assertTrue($view->clicked());

        $view = $btn->view(HttpFieldPath::named('foo')->prefix('bar_'));

        $this->assertEquals('foo[bar_btn]', $view->name());
        $this->assertEquals('<input type="submit" name="foo[bar_btn]" value="ok" />', (string) $view);
    }
}
