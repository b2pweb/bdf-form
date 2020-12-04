<?php

namespace Bdf\Form\Leaf;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class BooleanElementBuilderTest
 */
class BooleanElementBuilderTest extends TestCase
{
    /**
     * @var BooleanElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new BooleanElementBuilder();
    }

    /**
     *
     */
    public function test_buildElement()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(BooleanElement::class, $element);
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new NotBlank())->buildElement();

        $this->assertFalse($element->submit(null)->valid());
        $this->assertTrue($element->submit(4)->valid());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = $this->builder->transformer(function ($value, $element, $toPhp) {
            if ($toPhp) {
                switch ($value) {
                    case '-':
                        return false;
                    case '+':
                        return true;
                }
            } else {
                return $value ? '+' : '-';
            }
        })->buildElement();

        $this->assertTrue($element->submit('+')->value());
        $this->assertSame('+', $element->httpValue());
        $this->assertFalse($element->submit('-')->value());
        $this->assertSame('-', $element->httpValue());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = $this->builder->httpValue('ok')->buildElement();

        $element->import(true);
        $this->assertSame('ok', $element->httpValue());

        $element->import(false);
        $this->assertNull($element->httpValue());
    }

    /**
     *
     */
    public function test_httpValue_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->builder->httpValue('')->buildElement();
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->builder->value(true)->buildElement();

        $this->assertTrue($element->value());
    }

    /**
     *
     */
    public function test_required()
    {
        $element = $this->builder->required()->buildElement();

        $element->submit(null);
        $this->assertEquals('This value should not be blank.', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $element = $this->builder->required('my message')->buildElement();

        $element->submit(null);
        $this->assertEquals('my message', $element->error()->global());
    }
}
