<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Child\ChildInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HtmlFilterTest extends TestCase
{
    /**
     *
     */
    public function test_default_filter()
    {
        $value = '<span="class">"Test"</span>';
        $filter = new FilterVar();

        $this->assertEquals('"Test"', $filter->filter($value, $this->createMock(ChildInterface::class), null));
    }

    /**
     *
     */
    public function test_filter_array()
    {
        $value = ['foo' => '<span="class">"Test"</span>'];
        $filter = new FilterVar();

        $this->assertEquals(['foo' => '"Test"'], $filter->filter($value, $this->createMock(ChildInterface::class), null));
    }

    /**
     *
     */
    public function test_functionnal()
    {
        $builder = new FormBuilder();
        $builder->string('foo')->filter(FilterVar::class);
        $form = $builder->buildElement();

        $form->submit(['foo' => '<span="class">"Test"</span>']);

        $this->assertEquals('"Test"', $form['foo']->element()->value());
    }
}
