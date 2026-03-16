<?php

namespace Bdf\Form\Filter;

use Attribute;
use Bdf\Form\Aggregate\ArrayChildBuilder;
use Bdf\Form\Child\ChildInterface;
use Override;

/**
 * Filter empty values from an array
 *
 * @see ArrayChildBuilder::filterEmptyValues()
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EmptyArrayValuesFilter implements FilterInterface
{
    private static ?self $instance = null;

    #[Override]
    public function filter(mixed $value, ChildInterface $input, mixed $default): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $k => $v) {
            if ($v === null || $v === [] || $v === '') {
                unset($value[$k]);
            }
        }

        return $value;
    }

    /**
     * Get the filter instance
     *
     * @return static
     */
    public static function instance(): self
    {
        return self::$instance ??= new self;
    }
}
