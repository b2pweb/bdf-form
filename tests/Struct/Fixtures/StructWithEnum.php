<?php

namespace Bdf\Form\Struct\Fixtures;

class StructWithEnum
{
    public function __construct(
        public IntEnum $i,
        public SimpleEnum $s,
    ) {}
}

enum IntEnum: int
{
    case Foo = 1;
    case Bar = 2;
}

enum SimpleEnum
{
    case One;
    case Two;
}
