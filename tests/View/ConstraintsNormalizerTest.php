<?php

namespace Bdf\Form\View;

use Bdf\Form\Constraint\Closure;
use Bdf\Form\Csrf\CsrfValueValidator;
use Bdf\Form\Validator\ConstraintValueValidator;
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
        $this->assertEmpty(ConstraintsNormalizer::normalize(new ConstraintValueValidator()));
        $this->assertEmpty(ConstraintsNormalizer::normalize(new CsrfValueValidator()));
        $this->assertEquals([NotBlank::class => []], ConstraintsNormalizer::normalize(new ConstraintValueValidator([new NotBlank()])));
        $this->assertEquals([], ConstraintsNormalizer::normalize(new ConstraintValueValidator([])));
        $this->assertEquals([NotBlank::class => []], ConstraintsNormalizer::normalize(new ConstraintValueValidator([new NotBlank()])));
        $this->assertEquals([
            NotBlank::class => [],
            Length::class => ['min' => 3, 'max' => 5],
            LessThanOrEqual::class => ['value' => 42],
            NotEqualTo::class => ['value' => 666],
        ], ConstraintsNormalizer::normalize(new ConstraintValueValidator([new NotBlank(), new Length(['min' => 3, 'max' => 5]), new LessThanOrEqual(42), new NotEqualTo(666)])));

        $this->assertSame([Closure::class => []], ConstraintsNormalizer::normalize(new ConstraintValueValidator([new Closure(function () {})])));
        $this->assertSame([Closure::class => []], ConstraintsNormalizer::normalize(new ConstraintValueValidator([new Closure(function () {})]))); // The cache do not change the behavior
    }
}
