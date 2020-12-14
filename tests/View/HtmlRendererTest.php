<?php

namespace Bdf\Form\View;

use PHPUnit\Framework\TestCase;

class HtmlRendererTest extends TestCase
{
    /**
     *
     */
    public function test_attributes()
    {
        $this->assertEquals('', HtmlRenderer::attributes([]));
        $this->assertEquals(' foo="bar"', HtmlRenderer::attributes(['foo' => 'bar']));
        $this->assertEquals(' aaa="aaa" bbb="bbb"', HtmlRenderer::attributes(['aaa' => 'aaa', 'bbb' => 'bbb']));
        $this->assertEquals(' aaa', HtmlRenderer::attributes(['aaa' => true]));
        $this->assertEquals(' &lt;a&gt;a&lt;/a&gt;="&lt;b&gt;b&lt;/b&gt;"', HtmlRenderer::attributes(['<a>a</a>' => '<b>b</b>']));
        $this->assertEquals(' a="&quot;&gt;"', HtmlRenderer::attributes(['a' => '">']));
    }

    /**
     *
     */
    public function test_element()
    {
        $this->assertEquals('<test />', HtmlRenderer::element('test', []));
        $this->assertEquals('<test foo="bar" />', HtmlRenderer::element('test', ['foo' => 'bar']));
        $this->assertEquals('<test>inner</test>', HtmlRenderer::element('test', [], 'inner'));
        $this->assertEquals('<test foo="bar">inner</test>', HtmlRenderer::element('test', ['foo' => 'bar'], 'inner'));
    }
}
