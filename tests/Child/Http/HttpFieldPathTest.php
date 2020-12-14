<?php

namespace Bdf\Form\Child\Http;

use PHPUnit\Framework\TestCase;

class HttpFieldPathTest extends TestCase
{
    /**
     *
     */
    public function test_simple()
    {
        $this->assertSame('', (new HttpFieldPath())->get());
        $this->assertSame('pre_', (new HttpFieldPath())->prefix('pre_')->get());
        $this->assertSame('foo', (new HttpFieldPath())->add('foo')->get());
        $this->assertSame('foo[bar]', (new HttpFieldPath())->add('foo')->add('bar')->get());
        $this->assertSame('foo[bar][baz]', (new HttpFieldPath())->add('foo')->add('bar')->add('baz')->get());
        $this->assertSame('foo[pre_]', (new HttpFieldPath())->add('foo')->prefix('pre_')->get());
    }

    /**
     *
     */
    public function test_with_prefix()
    {
        $this->assertSame('a_foo', (new HttpFieldPath())->prefix('a_')->add('foo')->get());
        $this->assertSame('foo[b_bar]', (new HttpFieldPath())->add('foo')->prefix('b_')->add('bar')->get());
        $this->assertSame('foo[b_c_bar]', (new HttpFieldPath())->add('foo')->prefix('b_')->prefix('c_')->add('bar')->get());
        $this->assertSame('foo[b_c_bar][d_baz]', (new HttpFieldPath())->add('foo')->prefix('b_')->prefix('c_')->add('bar')->prefix('d_')->add('baz')->get());
    }

    /**
     *
     */
    public function test_add_should_return_new_instance()
    {
        $path = new HttpFieldPath();
        $path2 = $path->add('foo');

        $this->assertNotSame($path, $path2);
        $this->assertEquals('', (string) $path);
        $this->assertEquals('foo', (string) $path2);
    }

    /**
     *
     */
    public function test_prefix_should_return_new_instance()
    {
        $path = new HttpFieldPath();
        $path2 = $path->prefix('foo');

        $this->assertNotSame($path, $path2);
        $this->assertEquals('', (string) $path);
        $this->assertEquals('foo', (string) $path2);
    }

    /**
     *
     */
    public function test_named()
    {
        $this->assertEquals('foo', HttpFieldPath::named('foo')->get());
        $this->assertEquals('foo[bar]', HttpFieldPath::named('foo')->add('bar')->get());
    }

    /**
     *
     */
    public function test_prefixed()
    {
        $this->assertEquals('foo', HttpFieldPath::prefixed('foo')->get());
        $this->assertEquals('foobar', HttpFieldPath::prefixed('foo')->add('bar')->get());
    }

    /**
     *
     */
    public function test_empty()
    {
        $this->assertEquals('', HttpFieldPath::empty()->get());
        $this->assertEquals('bar', HttpFieldPath::empty()->add('bar')->get());
        $this->assertSame(HttpFieldPath::empty(), HttpFieldPath::empty());
    }
}
