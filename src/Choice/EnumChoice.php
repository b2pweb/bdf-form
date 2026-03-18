<?php

namespace Bdf\Form\Choice;

use BackedEnum;
use Closure;
use Override;

use function assert;
use function is_subclass_of;

/**
 * Adapt a backed enum to a choice
 *
 * @template E as BackedEnum
 * @implements ChoiceInterface<int|string>
 */
final readonly class EnumChoice implements ChoiceInterface
{
    public function __construct(
        /**
         * @var class-string<E>
         */
        private string $enumClass,

        /**
         * Function use to generate the label from the enum case.
         * If not set, {@see BackedEnum::$name} is used as label.
         *
         * @var (Closure(E):string)|null
         */
        private ?Closure $label = null,
    ) {
        assert(is_subclass_of($enumClass, BackedEnum::class));
    }

    #[Override]
    public function values(): array
    {
        $values = [];

        foreach ($this->enumClass::cases() as $value) {
            $values[$this->label ? ($this->label)($value) : $value->name] = $value->value;
        }

        return $values;
    }

    #[Override]
    public function view(?callable $configuration = null): array
    {
        $view = [];

        foreach ($this->enumClass::cases() as $value) {
            $view[] = $choice = new ChoiceView($value->value, $this->label ? ($this->label)($value) : $value->name);

            if ($configuration !== null) {
                $configuration($choice);
            }
        }

        return $view;
    }
}
