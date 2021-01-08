<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Child\ChildInterface;
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

        $this->assertEquals('foobar', $filter->filter('foo', $this->createMock(ChildInterface::class)));
    }
}
