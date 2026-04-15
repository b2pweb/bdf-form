<?php

namespace Bdf\Form\Struct\Fixtures;

use Bdf\Form\Attribute\Element\Date\DateFormat;
use DateTime;
use DateTimeImmutable;

class DtoWithDate
{
    public function __construct(
        #[DateFormat('Y-m-d')]
        public DateTimeImmutable $start,

        #[DateFormat('Y-m-d')]
        public CustomDate $end,
    ) {}
}

class CustomDate extends DateTime {}
