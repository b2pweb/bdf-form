<?php

namespace Bdf\Form\Struct\Fixtures;

use Bdf\Form\Attribute\Aggregate\Count;
use Bdf\Form\Attribute\Aggregate\StructElement;
use Symfony\Component\Validator\Constraints\Range;

final readonly class Shape
{
    public function __construct(
        public Color $color,

        #[Count(min: 3), StructElement(Point::class)]
        public array $points,
    ) {}
}

final readonly class Point
{
    public function __construct(
        public float $x,
        public float $y,
    ) {}
}

final readonly class Color
{
    public function __construct(
        #[Range(min: 0, max: 255)]
        public int $red,

        #[Range(min: 0, max: 255)]
        public int $green,

        #[Range(min: 0, max: 255)]
        public int $blue,
    ) {}
}
