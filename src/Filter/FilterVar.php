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
    public function __construct(int $filter = FILTER_SANITIZE_STRING, int $flags = FILTER_FLAG_NO_ENCODE_QUOTES)
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
            return filter_var($value, $this->filter, $this->flags);
        }

        foreach ($value as &$item) {
            $item = filter_var($item, $this->filter, $this->flags);
        }

        return $value;
    }
}
