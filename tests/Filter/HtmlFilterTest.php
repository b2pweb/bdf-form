<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\FormBuilder;
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
        $filter = new HtmlFilter();

        $this->assertEquals('"Test"', $filter->filter($value, null));
    }

    /**
     *
     */
    public function test_filter_array()
    {
        $value = ['foo' => '<span="class">"Test"</span>'];
        $filter = new HtmlFilter();

        $this->assertEquals(['foo' => '"Test"'], $filter->filter($value, null));
    }

    /**
     *
     */
    public function test_functionnal()
    {
        $builder = new FormBuilder();
        $builder->string('foo')->filter(HtmlFilter::class);
        $form = $builder->buildElement();

        $form->submit(['foo' => '<span="class">"Test"</span>']);

        $this->assertEquals('"Test"', $form['foo']->element()->value());
    }
}
