<?php

namespace Bdf\Form\Util;

/**
 * Utility class for handle http values
 */
final class HttpValue
{
    /**
     * Check if the given value should be considered as empty
     * Unlike php's `empty()` function, '0', false, 0, 0.0 are not considered as empty
     *
     * @param mixed $value Value to check
     *
     * @return bool true if the value is empty
     */
    public static function isEmpty($value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Get the http value or the default one if it's empty
     * Note: If not default value is provided, the http value is returned
     *
     * @param mixed $value
     * @param mixed $default
     *
     * @return mixed The value or the default
     */
    public static function orDefault($value, $default)
    {
        if ($default === null || !self::isEmpty($value)) {
            return $value;
        }

        return $default;
    }
}
