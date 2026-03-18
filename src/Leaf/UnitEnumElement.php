<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Override;
use TypeError;
use UnitEnum;

use function defined;
use function is_string;
use function sprintf;

/**
 * Field element that handles unit enum
 * The enum name will be used to resolve the enum case
 *
 * @extends LeafElement<UnitEnum>
 */
final class UnitEnumElement extends LeafElement
{
    public function __construct(
        /**
         * @var class-string<UnitEnum>
         */
        private readonly string $enumClass,

        ?ValueValidatorInterface $validator = null,
        ?TransformerInterface $transformer = null,
        ?ChoiceInterface $choices = null
    ) {
        parent::__construct($validator, $transformer, $choices);
    }

    #[Override]
    protected function toPhp(mixed $httpValue): ?UnitEnum
    {
        if (is_string($httpValue) && defined($this->enumClass . '::' . $httpValue)) {
            return $this->enumClass::{$httpValue};
        }

        if ($httpValue instanceof $this->enumClass) {
            return $httpValue;
        }

        return null;
    }

    #[Override]
    protected function toHttp(mixed $phpValue): string|int|null
    {
        if ($phpValue instanceof $this->enumClass) {
            return $phpValue->name;
        }

        return null;
    }

    #[Override]
    protected function tryCast(mixed $value): ?UnitEnum
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof $this->enumClass) {
            return $value;
        }

        throw new TypeError(sprintf("The import()'ed value of a %s must be an instance of %s or null", self::class, $this->enumClass));
    }

    #[Override]
    protected function sanitize(mixed $rawValue)
    {
        return $rawValue;
    }
}
