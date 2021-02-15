<?php

namespace Bdf\Form\Validator;

use Bdf\Form\Constraint\Closure;
use Bdf\Form\Leaf\StringElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Class ConstraintValueValidatorTest
 */
class ConstraintValueValidatorTest extends TestCase
{
    /**
     *
     */
    public function test_validate_success()
    {
        $element = new StringElement();
        $validator = new ConstraintValueValidator([new NotBlank()]);

        $this->assertTrue($validator->validate('value', $element)->empty());
    }

    /**
     *
     */
    public function test_validate_without_constraints()
    {
        $element = new StringElement();
        $validator = new ConstraintValueValidator([]);

        $this->assertTrue($validator->validate('value', $element)->empty());
    }

    /**
     *
     */
    public function test_validate_chain()
    {
        $calls = [];
        $element = new StringElement();
        $validator = new ConstraintValueValidator([
            new Closure(function () use(&$calls) { $calls[] = 'A'; }),
            new Closure(function () use(&$calls) { $calls[] = 'B'; }),
            new Closure(function () use(&$calls) { $calls[] = 'C'; }),
        ]);

        $this->assertTrue($validator->validate('value', $element)->empty());
        $this->assertSame(['A', 'B', 'C'], $calls);
    }

    /**
     *
     */
    public function test_validate_chain_with_error_should_stop_on_first_error()
    {
        $calls = [];
        $element = new StringElement();
        $validator = new ConstraintValueValidator([
            new Closure(function () use(&$calls) { $calls[] = 'A'; }),
            new Closure(function () use(&$calls) { $calls[] = 'B'; return 'error'; }),
            new Closure(function () use(&$calls) { $calls[] = 'C'; }),
        ]);

        $this->assertEquals('error', $validator->validate('value', $element)->global());
        $this->assertSame(['A', 'B'], $calls);
    }

    /**
     *
     */
    public function test_validate_error()
    {
        $element = new StringElement();
        $validator = new ConstraintValueValidator([new NotBlank()]);

        $error = $validator->validate('', $element);

        $this->assertFalse($error->empty());
        $this->assertEquals('This value should not be blank.', $error->global());
    }

    /**
     *
     */
    public function test_onTransformerException()
    {
        $element = new StringElement();
        $validator = new ConstraintValueValidator();

        $error = $validator->onTransformerException(new \Exception('my error'), 'foo', $element);

        $this->assertFalse($error->empty());
        $this->assertEquals('my error', $error->global());
        $this->assertEquals('TRANSFORM_ERROR', $error->code());
    }

    /**
     *
     */
    public function test_onTransformerException_ignoreException()
    {
        $element = new StringElement();
        $validator = new ConstraintValueValidator([], new TransformerExceptionConstraint(['ignoreException' => true]));

        $error = $validator->onTransformerException(new \Exception('my error'), 'foo', $element);

        $this->assertTrue($error->empty());
    }

    /**
     *
     */
    public function test_onTransformerException_custom_message_and_code()
    {
        $element = new StringElement();
        $validator = new ConstraintValueValidator([], new TransformerExceptionConstraint(['message' => 'message', 'code' => 'CODE_ERROR']));

        $error = $validator->onTransformerException(new \Exception('my error'), 'foo', $element);

        $this->assertFalse($error->empty());
        $this->assertEquals('message', $error->global());
        $this->assertEquals('CODE_ERROR', $error->code());
    }
}
