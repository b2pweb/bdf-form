<?php


namespace Bdf\Form\Phone\Transformer;

use Attribute;
use Bdf\Form\ElementInterface;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\Transformer\TransformerInterface;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Transformer PhoneNumber instance to string with a format
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class PhoneNumberToStringTransformer implements TransformerInterface
{
    /**
     * @var PhoneNumberFormat::*
     */
    private $format;

    /**
     * @var bool
     */
    private $formatIfInvalid;

    /**
     * @var PhoneNumberUtil|null
     */
    private $formatter;

    /**
     * PhoneNumberToStringTransformer constructor.
     *
     * @param PhoneNumberFormat::* $format
     * @param bool $formatIfInvalid
     * @param PhoneNumberUtil|null $formatter
     */
    public function __construct(int $format = PhoneNumberFormat::E164, bool $formatIfInvalid = false, ?PhoneNumberUtil $formatter = null)
    {
        $this->format = $format;
        $this->formatIfInvalid = $formatIfInvalid;
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function transformToHttp($value, ElementInterface $input): ?PhoneNumber
    {
        if ($value === null) {
            return null;
        }

        if ($input instanceof PhoneElement) {
            return $input->parseValue($value);
        }

        $formatter = $this->formatter ?? PhoneNumberUtil::getInstance();

        return $formatter->parse($value, null, null, true);
    }

    /**
     * {@inheritdoc}
     */
    public function transformFromHttp($value, ElementInterface $input): ?string
    {
        if (!$value instanceof PhoneNumber) {
            return null;
        }

        $formatter = $this->formatter ?? ($input instanceof PhoneElement ? $input->getFormatter() : PhoneNumberUtil::getInstance());

        if ((!$this->formatIfInvalid && !$formatter->isValidNumber($value)) || !$value->getNationalNumber()) {
            return $value->getRawInput();
        }

        return $formatter->format($value, $this->format);
    }
}
