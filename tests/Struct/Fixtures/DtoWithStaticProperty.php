<?php

namespace Bdf\Form\Struct\Fixtures;

class DtoWithStaticProperty
{
    public static string $staticProperty = 'ignored';

    public function __construct(
        public string $name,
        public int $value,
    ) {}
}
