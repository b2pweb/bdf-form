<?php

namespace Bdf\Form\Leaf\Helper;

use Bdf\Form\Child\Http\HttpFieldPath;
use PHPUnit\Framework\TestCase;

class EmailElementTest extends TestCase
{
    /**
     *
     */
    public function test_view()
    {
        $this->assertEquals('<input type="email" name="foo" value="" />', (string) (new EmailElement())->view(HttpFieldPath::named('foo')));
    }
}
