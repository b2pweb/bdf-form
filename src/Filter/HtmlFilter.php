<?php

namespace Bdf\Form\Filter;

/**
 * HtmlFilter
 *
 * @tdo rename
 */
class HtmlFilter implements FilterInterface
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
     * HtmlFilter constructor.
     *
     * @param int $filter  The filter option. Sanitize HTML by default.
     * @param int $flags
     */
    public function __construct($filter = FILTER_SANITIZE_STRING, $flags = FILTER_FLAG_NO_ENCODE_QUOTES)
    {
        $this->filter = $filter;
        $this->flags = $flags;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($value, $input)
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
