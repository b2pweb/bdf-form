<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Child\ChildInterface;
use PHPUnit\Framework\TestCase;

class EmptyArrayValuesFilterTest extends TestCase
{
    /**
     *
     */
    public function test_filter()
    {
        $filter = new EmptyArrayValuesFilter();

        $this->assertSame([
            0 => 'foo',
            2 => 'bar',
            4 => 0,
            5 => '0',
            6 => false,
            7 => true,
            8 => ['e'],
        ], $filter->filter([
            'foo',
            null,
            'bar',
            [],
            0,
            '0',
            false,
            true,
            ['e'],
            '',
        ], $this->createMock(ChildInterface::class)));
    }

    public function test_instance()
    {
        $this->assertInstanceOf(EmptyArrayValuesFilter::class, EmptyArrayValuesFilter::instance());
        $this->assertSame(EmptyArrayValuesFilter::instance(), EmptyArrayValuesFilter::instance());
    }
}
