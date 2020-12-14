<?php

namespace Bdf\Form\View;

use Bdf\Form\Csrf\CsrfValueValidator;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\NullValueValidator;
use Bdf\Validator\Constraints\Chain;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;

class ConstraintsNormalizerTest extends TestCase
{
    /**
     *
     */
    public function test_normalize()
    {
        $this->assertEmpty(ConstraintsNormalizer::normalize(new NullValueValidator()));
        $this->assertEmpty(ConstraintsNormalizer::normalize(new CsrfValueValidator()));
        $this->assertEquals([NotBlank::class => []], ConstraintsNormalizer::normalize(new ConstraintValueValidator(new NotBlank())));
        $this->assertEquals([], ConstraintsNormalizer::normalize(new ConstraintValueValidator(new Chain())));
        $this->assertEquals([NotBlank::class => []], ConstraintsNormalizer::normalize(new ConstraintValueValidator(new Chain([new NotBlank()]))));
        $this->assertEquals([
            NotBlank::class => [],
            Length::class => ['min' => 3, 'max' => 5],
            LessThanOrEqual::class => ['value' => 42],
            NotEqualTo::class => ['value' => 666],
        ], ConstraintsNormalizer::normalize(new ConstraintValueValidator(new Chain([new NotBlank(), new Length(null, 3, 5), new LessThanOrEqual(42), new NotEqualTo(666)]))));
    }
}
