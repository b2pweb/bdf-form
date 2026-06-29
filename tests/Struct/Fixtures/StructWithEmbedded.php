<?php

namespace Bdf\Form\Struct\Fixtures;

use Symfony\Component\Validator\Constraints\Positive;

final readonly class StructWithEmbedded
{
    public function __construct(
        #[Positive]
        public int $id,
        public SimpleDto $embedded,
    ) {}
}
