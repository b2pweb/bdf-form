<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\ArrayChildBuilder;
use Bdf\Form\Child\ChildInterface;

/**
 * Filter empty values from an array
 *
 * @see ArrayChildBuilder::filterEmptyValues()
 */
final class EmptyArrayValuesFilter implements FilterInterface
{
    /**
     * @var EmptyArrayValuesFilter
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function filter($value, ChildInterface $input)
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
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self;
    }
}
