<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Child\ChildInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class FilterVarTest extends TestCase
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
    public function test_html_filter_utf8()
    {
        $value = '"éà@è\'';
        $filter = new FilterVar(FilterVar::HTML_FILTER);

        $this->assertEquals('"éà@è\'', $filter->filter($value, $this->createMock(ChildInterface::class), null));
    }

    /**
     *
     */
    public function test_html_filter_utf8_with_encode_flags()
    {
        $value = '"éà@è\'';
        $filter = new FilterVar(FilterVar::HTML_FILTER, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);

        $this->assertEquals('&#34;&#195;&#169;&#195;&#160;@&#195;&#168;&#39;', $filter->filter($value, $this->createMock(ChildInterface::class), null));
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

    /**
     *
     */
    public function test_custom_filter()
    {
        $filter = new FilterVar(FILTER_SANITIZE_EMAIL);

        $this->assertEquals('foo@example.com', $filter->filter('   ))foo@example.com', $this->createMock(ChildInterface::class), null));
    }
}
