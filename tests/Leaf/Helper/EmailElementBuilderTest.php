<?php

namespace Bdf\Form\Leaf\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email;

class EmailElementBuilderTest extends TestCase
{
    /**
     * @var EmailElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new EmailElementBuilder();
    }

    /**
     *
     */
    public function test_default()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(EmailElement::class, $element);

        $this->assertFalse($element->submit('foo')->valid());
        $this->assertEquals('This value is not a valid email address.', $element->error()->global());
        $this->assertContains($element->error()->code(), ['STRICT_CHECK_FAILED_ERROR', 'INVALID_FORMAT_ERROR']);

        $this->assertTrue($element->submit('foo@example.com')->valid());
    }

    /**
     *
     */
    public function test_mode()
    {
        if (defined(Email::class.'::VALIDATION_MODE_HTML5_ALLOW_NO_TLD')) {
            $element = $this->builder->mode(Email::VALIDATION_MODE_HTML5_ALLOW_NO_TLD)->buildElement();
            $this->assertTrue($element->submit('foo@bar')->valid());
        }

        $element = $this->builder->mode(Email::VALIDATION_MODE_HTML5)->buildElement();
        $this->assertFalse($element->submit('foo@bar')->valid());
    }

    /**
     *
     */
    public function test_errorMessage()
    {
        $element = $this->builder->errorMessage('my error')->buildElement();

        $this->assertInstanceOf(EmailElement::class, $element);

        $this->assertFalse($element->submit('foo')->valid());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_normalizer()
    {
        $element = $this->builder->normalizer(function (string $value) {
            return strtr($value, ['[at]' => '@', '[dot]' => '.']);
        })->buildElement();

        $this->assertInstanceOf(EmailElement::class, $element);

        $this->assertTrue($element->submit('foo[at]bar[dot]com')->valid());
        $this->assertEquals('foo[at]bar[dot]com', $element->value());
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

        $this->assertInstanceOf(EmailElement::class, $element);

        $this->assertFalse($element->submit('foo')->valid());
        $this->assertEquals('my error', $element->error()->global());
    }
}
