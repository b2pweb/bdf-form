<?php

namespace Bdf\Form\Filter;

use Attribute;
use Bdf\Form\Child\ChildInterface;

/**
 * Adapt filter_var() to FilterInterface
 * By default, configured to filter HTML values
 *
 * @see filter_var()
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class FilterVar implements FilterInterface
{
    const HTML_FILTER = -1;

    /**
     * The filter option given to filter_var
     *
     * @var int
     */
    private $filter;

    /**
     * The flag option of the filter given to filter_var
     *
     * @var int
     */
    private $flags;

    /**
     * FilterVar constructor.
     *
     * @param int $filter  The filter option. Sanitize HTML by default.
     * @param int $flags
     */
    public function __construct(int $filter = self::HTML_FILTER, int $flags = FILTER_FLAG_NO_ENCODE_QUOTES)
    {
        $this->filter = $filter;
        $this->flags = $flags;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($value, ChildInterface $input, $default)
    {
        if (!is_array($value)) {
            return $this->apply($value);
        }

        foreach ($value as &$item) {
            $item = $this->apply($item);
        }

        return $value;
    }

    private function apply($value)
    {
        if ($this->filter !== self::HTML_FILTER) {
            return filter_var($value, $this->filter, $this->flags);
        }

        if (PHP_VERSION_ID < 80100) {
            return filter_var($value, FILTER_SANITIZE_STRING, $this->flags);
        }

        // FILTER_SANITIZE_STRING is deprecated in PHP 8.1
        $value = strip_tags($value);

        // Apply "strip" filters
        if (($this->flags & ~FILTER_FLAG_NO_ENCODE_QUOTES) !== 0) {
            $value = filter_var($value, FILTER_UNSAFE_RAW, $this->flags);
        }

        // Encode quotes
        if (($this->flags & FILTER_FLAG_NO_ENCODE_QUOTES) === 0) {
            $value = str_replace(['"', "'"], ['&#34;', '&#39;'], $value);
        }

        return $value;
    }
}
