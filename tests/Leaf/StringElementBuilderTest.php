<?php

namespace Bdf\Form\Leaf;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Class StringElementBuilderTest
 */
class StringElementBuilderTest extends TestCase
{
    /**
     * @var StringElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new StringElementBuilder();
    }

    /**
     *
     */
    public function test_buildElement()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(StringElement::class, $element);
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new NotEqualTo('hello'))->buildElement();

        $this->assertFalse($element->submit('hello')->valid());
        $this->assertTrue($element->submit('world')->valid());
    }

    /**
     *
     */
    public function test_transformer_append()
    {
        $element = $this->builder
            ->transformer(function ($value) { return $value . 'a'; }, true)
            ->transformer(function ($value) { return $value . 'b'; }, true)
            ->buildElement()
        ;

        $this->assertEquals('_ba', $element->submit('_')->value());
    }

    /**
     *
     */
    public function test_transformer_prepend()
    {
        $element = $this->builder
            ->transformer(function ($value) { return $value . 'a'; }, false)
            ->transformer(function ($value) { return $value . 'b'; }, false)
            ->buildElement()
        ;

        $this->assertEquals('_ab', $element->submit('_')->value());
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->builder->value('default')->buildElement();

        $this->assertSame('default', $element->value());
    }

    /**
     *
     */
    public function test_length()
    {
        $element = $this->builder->length(['max' => 3])->buildElement();

        $this->assertFalse($element->submit('aaaa')->valid());
        $this->assertTrue($element->submit('aaa')->valid());
    }

    /**
     *
     */
    public function test_regex()
    {
        $element = $this->builder->regex('^j')->buildElement();

        $this->assertFalse($element->submit('Bill')->valid());
        $this->assertFalse($element->submit('John')->valid());
    }
}
