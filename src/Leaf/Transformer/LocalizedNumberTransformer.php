<?php

namespace Bdf\Form\Leaf\Transformer;

use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use InvalidArgumentException;
use Locale;
use NumberFormatter;

/**
 * Transformer localized string number to native PHP number (int or double)
 *
 * Inspired from : https://github.com/symfony/symfony/blob/5.x/src/Symfony/Component/Form/Extension/Core/DataTransformer/NumberToLocalizedStringTransformer.php
 *
 * @template T as numeric
 */
class LocalizedNumberTransformer implements TransformerInterface
{
    /**
     * Number of digit to keep after the comma
     *
     * @var int|null
     */
    private $scale;

    /**
     * @var int
     * @psalm-var NumberFormatter::ROUND_*
     */
    private $roundingMode;

    /**
     * Group by thousand or not
     *
     * @var bool
     */
    private $grouping;

    /**
     * The locale to use
     * null for use the current locale
     *
     * @var string|null
     */
    private $locale;

    /**
     * LocalizedNumberTransformer constructor.
     *
     * @param int|null $scale Number of digit to keep after the comma. Null to keep all digits (do not round)
     * @param bool $grouping Group by thousand or not
     * @param NumberFormatter::ROUND_* $roundingMode
     * @param string|null $locale The locale to use. null for use the current locale
     */
    public function __construct(?int $scale = null, bool $grouping = false, int $roundingMode = NumberFormatter::ROUND_HALFUP, ?string $locale = null)
    {
        $this->scale = $scale;
        $this->grouping = $grouping;
        $this->roundingMode = $roundingMode;
        $this->locale = $locale;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException If the given value is not numeric or cannot be formatted
     */
    final public function transformToHttp($value, ElementInterface $input): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Expected a numeric or null.');
        }

        $formatter = $this->getNumberFormatter();
        $value = $formatter->format($value);

        $this->checkError($formatter);

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @return T|null The numeric value
     *
     * @throws InvalidArgumentException If the given value is not scalar or cannot be parsed
     */
    final public function transformFromHttp($value, ElementInterface $input)
    {
        if ($value !== null && !is_scalar($value)) {
            throw new InvalidArgumentException('Expected a scalar or null.');
        }

        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;

        $formatter = $this->getNumberFormatter();
        $decSep = $formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        // Normalize "standard" decimal format to locale format
        if ($decSep !== '.') {
            $value = str_replace('.', $decSep, $value);
        }

        if (str_contains($value, $decSep)) {
            $type = NumberFormatter::TYPE_DOUBLE;
        } else {
            $type = PHP_INT_SIZE === 8 ? NumberFormatter::TYPE_INT64 : NumberFormatter::TYPE_INT32;
        }

        $result = $formatter->parse($value, $type);
        $this->checkError($formatter);

        return $this->cast($this->round($result));
    }

    /**
     * Create the NumberFormatter instance
     *
     * @return NumberFormatter
     */
    private function getNumberFormatter(): NumberFormatter
    {
        $formatter = new NumberFormatter($this->locale ?? Locale::getDefault(), NumberFormatter::DECIMAL);

        if (null !== $this->scale) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $this->scale);
            $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, $this->roundingMode);
        }

        $formatter->setAttribute(NumberFormatter::GROUPING_USED, $this->grouping);

        return $formatter;
    }

    /**
     * Cast the number to the desired type
     *
     * @return T
     */
    protected function cast($value)
    {
        return $value;
    }

    /**
     * Rounds a number according to the configured scale and rounding mode
     *
     * @param int|float $number A number
     *
     * @return int|float The rounded number
     */
    private function round($number)
    {
        if (is_int($number) || $this->scale === null) {
            return $number;
        }

        switch ($this->roundingMode) {
            case NumberFormatter::ROUND_HALFEVEN:
                return round($number, $this->scale, PHP_ROUND_HALF_EVEN);
            case NumberFormatter::ROUND_HALFUP:
                return round($number, $this->scale, PHP_ROUND_HALF_UP);
            case NumberFormatter::ROUND_HALFDOWN:
                return round($number, $this->scale, PHP_ROUND_HALF_DOWN);
        }

        $coef = 10 ** $this->scale;
        $number *= $coef;

        switch ($this->roundingMode) {
            case NumberFormatter::ROUND_CEILING:
                $number = ceil($number);
                break;
            case NumberFormatter::ROUND_FLOOR:
                $number = floor($number);
                break;
            case NumberFormatter::ROUND_UP:
                $number = $number > 0 ? ceil($number) : floor($number);
                break;
            case NumberFormatter::ROUND_DOWN:
                $number = $number > 0 ? floor($number) : ceil($number);
                break;
        }

        return $number / $coef;
    }

    /**
     * Check if the formatter is in error state, and throw exception
     *
     * @param NumberFormatter $formatter
     * @throws InvalidArgumentException If the formatter has an error
     */
    private function checkError(NumberFormatter $formatter): void
    {
        if (intl_is_failure($formatter->getErrorCode())) {
            throw new InvalidArgumentException($formatter->getErrorMessage());
        }
    }
}
