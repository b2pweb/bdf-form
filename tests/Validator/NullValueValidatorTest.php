<?php

namespace Bdf\Form\Validator;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class NullValueValidatorTest
 */
class NullValueValidatorTest extends TestCase
{
    /**
     *
     */
    public function test_instance()
    {
        $this->assertInstanceOf(NullValueValidator::class, NullValueValidator::instance());
        $this->assertSame(NullValueValidator::instance(), NullValueValidator::instance());
    }

    /**
     *
     */
    public function test_constraints()
    {
        $this->assertSame([], NullValueValidator::instance()->constraints());
    }

    /**
     *
     */
    public function test_validate()
    {
        $this->assertTrue((new NullValueValidator())->validate('value', $this->createMock(ElementInterface::class))->empty());
    }
}
