<?php

namespace Bdf\Form\Filter;

use Attribute;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildInterface;

/**
 * Perform a trim on the input value
 * Supports trim of utf-8 white spaces
 *
 * @see ChildBuilder::trim() For enable trim filter
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class TrimFilter implements FilterInterface
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function filter($value, ChildInterface $input, $default)
    {
        if (!is_string($value)) {
            return $value;
        }

        // unicode trim
        if (null !== $result = @preg_replace('/^[\pZ\p{Cc}]+|[\pZ\p{Cc}]+$/u', '', $value)) {
            return $result;
        }

        return trim($value);
    }

    /**
     * Get the trim filter instance
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
