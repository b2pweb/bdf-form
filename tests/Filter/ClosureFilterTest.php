<?php

namespace Bdf\Form\Filter;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ClosureFilterTest extends TestCase
{
    /**
     *
     */
    public function test_default_filter()
    {
        $filter = new ClosureFilter(function($value) {
            return $value.'bar';
        });

        $this->assertEquals('foobar', $filter->filter('foo', null));
    }
}
