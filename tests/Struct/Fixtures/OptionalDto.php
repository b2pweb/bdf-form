<?php

namespace Bdf\Form\Struct\Fixtures;

class OptionalDto
{
    public function __construct(
        public ?string $name,
        public int $value = -1,
    ) {}
}
