<?php

namespace Bdf\Form\Struct\Fixtures;

use Bdf\Form\Filter\TrimFilter;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;

final readonly class ConstraintDto
{
    public function __construct(
        #[TrimFilter, Regex('/^[A-Z-]{2,12}$/i')]
        public string $name,

        #[Positive, LessThan(250)]
        public int $value,
    ) {}
}
