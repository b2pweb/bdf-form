<?php

namespace Bdf\Form\Leaf;

use BackedEnum;
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Override;

use TypeError;
use UnitEnum;

use function is_int;
use function is_scalar;
use function is_string;
use function sprintf;

/**
 * Field element that handles backed enum
 * The enum value will be used to resolve the enum case
 *
 * @extends LeafElement<BackedEnum>
 */
final class BackedEnumElement extends LeafElement
{
    public function __construct(
        /**
         * @var class-string<BackedEnum>
         */
        private readonly string $enumClass,

        ?ValueValidatorInterface $validator = null,
        ?TransformerInterface $transformer = null,
        ?ChoiceInterface $choices = null
    ) {
        parent::__construct($validator, $transformer, $choices);
    }

    #[Override]
    protected function toPhp(mixed $httpValue): ?BackedEnum
    {
        if (is_int($httpValue) || is_string($httpValue)) {
            return $this->enumClass::tryFrom($httpValue);
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
            return $phpValue->value;
        }

        return null;
    }

    #[Override]
    protected function tryCast(mixed $value): ?BackedEnum
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
