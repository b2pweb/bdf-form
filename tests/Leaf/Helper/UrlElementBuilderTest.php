<?php

namespace Bdf\Form\Leaf\Helper;

use PHPUnit\Framework\TestCase;

class UrlElementBuilderTest extends TestCase
{
    /**
     * @var UrlElementBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new UrlElementBuilder();
    }

    /**
     *
     */
    public function test_default()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(UrlElement::class, $element);

        $this->assertFalse($element->submit('foo')->valid());
        $this->assertEquals('This value is not a valid URL.', $element->error()->global());
        $this->assertEquals('INVALID_URL_ERROR', $element->error()->code());

        $this->assertTrue($element->submit('http://example.com')->valid());
    }

    /**
     *
     */
    public function test_protocols()
    {
        $element = $this->builder->protocols('ftp', 'ssh')->buildElement();

        $this->assertInstanceOf(UrlElement::class, $element);

        $this->assertFalse($element->submit('http://example.com')->valid());
        $this->assertTrue($element->submit('ftp://example.com')->valid());
        $this->assertTrue($element->submit('ssh://example.com')->valid());
    }

    /**
     *
     */
    public function test_relativeProtocol()
    {
        $element = $this->builder->relativeProtocol()->buildElement();
        $this->assertTrue($element->submit('//example.com')->valid());

        $element = $this->builder->relativeProtocol(false)->buildElement();
        $this->assertFalse($element->submit('//example.com')->valid());
    }

    /**
     *
     */
    public function test_errorMessage()
    {
        $element = $this->builder->errorMessage('my error')->buildElement();

        $this->assertInstanceOf(UrlElement::class, $element);

        $this->assertFalse($element->submit('foo')->valid());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_normalizer()
    {
        $element = $this->builder->normalizer(function (string $value) {
            return 'http://example.com/'.$value;
        })->buildElement();

        $this->assertInstanceOf(UrlElement::class, $element);

        $this->assertTrue($element->submit('foo')->valid());
        $this->assertEquals('foo', $element->value());
    }

    /**
     *
     */
    public function test_disableConstraint()
    {
        $element = $this->builder->disableConstraint()->buildElement();

        $this->assertTrue($element->submit('foo')->valid());
    }

    /**
     *
     */
    public function test_useConstraint()
    {
        $element = $this->builder
            ->disableConstraint()
            ->useConstraint(['message' => 'my error'])
            ->buildElement()
        ;

        $this->assertInstanceOf(UrlElement::class, $element);

        $this->assertFalse($element->submit('foo')->valid());
        $this->assertEquals('my error', $element->error()->global());
    }
}
