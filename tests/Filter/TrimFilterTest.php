<?php

namespace Bdf\Form\Filter;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class TrimFilterTest
 */
class TrimFilterTest extends TestCase
{
    /**
     *
     */
    public function test_trim_utf8()
    {
        $filter = new TrimFilter();

        $this->assertEquals('aaa', $filter->filter("\xc2\xa0 aaa\xe2\x80\x83", $this->createMock(ElementInterface::class)));
    }

    /**
     *
     */
    public function test_trim_not_utf8()
    {
        $filter = new TrimFilter();

        $this->assertEquals(utf8_decode('é'), $filter->filter(utf8_decode(' é '), $this->createMock(ElementInterface::class)));
    }
}
