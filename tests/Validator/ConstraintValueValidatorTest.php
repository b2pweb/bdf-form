<?php

namespace Bdf\Form\Validator;

use Bdf\Form\Leaf\StringElement;
use Bdf\Validator\Constraints\Chain;
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
        $validator = new ConstraintValueValidator(new NotBlank());

        $this->assertTrue($validator->validate('value', $element)->empty());
    }

    /**
     *
     */
    public function test_validate_error()
    {
        $element = new StringElement();
        $validator = new ConstraintValueValidator(new NotBlank());

        $error = $validator->validate('', $element);

        $this->assertFalse($error->empty());
        $this->assertEquals('This value should not be blank.', $error->global());
    }

    /**
     *
     */
    public function test_fromConstraints()
    {
        $this->assertEquals(new NullValueValidator(), ConstraintValueValidator::fromConstraints([]));
        $this->assertEquals(new ConstraintValueValidator(new NotBlank()), ConstraintValueValidator::fromConstraints([new NotBlank()]));
        $this->assertEquals([new NotBlank()], ConstraintValueValidator::fromConstraints([new NotBlank()])->constraints());
        $this->assertEquals(new ConstraintValueValidator(new Chain(['constraints' => [new NotBlank(), new NotEqualTo('foo')]])), ConstraintValueValidator::fromConstraints([new NotBlank(), new NotEqualTo('foo')]));
        $this->assertEquals([new NotBlank(['groups' => ['Default']]), new NotEqualTo(['value' => 'foo', 'groups' => ['Default']])], ConstraintValueValidator::fromConstraints([new NotBlank(), new NotEqualTo('foo')])->constraints());
    }
}
