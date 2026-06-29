<?php

namespace Bdf\Form\Struct\Fixtures;

class SimpleDto
{
    public function __construct(
        public string $name,
        public int $value,
    ) {}
}
