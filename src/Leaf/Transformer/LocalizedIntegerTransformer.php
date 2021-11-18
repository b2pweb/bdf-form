<?php

namespace Bdf\Form\Leaf\Transformer;

use Attribute;
use NumberFormatter;

/**
 * Localized number transformer for integer value
 *
 * @extends LocalizedNumberTransformer<int>
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class LocalizedIntegerTransformer extends LocalizedNumberTransformer
{
    /**
     * LocalizedIntegerTransformer constructor.
     *
     * @param bool $grouping Group by thousand or not
     * @param NumberFormatter::ROUND_* $roundingMode
     * @param string|null $locale The locale to use. null for use the current locale
     */
    public function __construct(bool $grouping = false, int $roundingMode = NumberFormatter::ROUND_HALFUP, ?string $locale = null)
    {
        parent::__construct(0, $grouping, $roundingMode, $locale);
    }

    /**
     * {@inheritdoc}
     */
    protected function cast($value): int
    {
        return $value;
    }
}
