<?php


namespace Bdf\Form\Phone\Transformer;

use Bdf\Form\ElementInterface;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\Transformer\TransformerInterface;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Transformer PhoneNumber instance to string with a format
 */
final class PhoneNumberToStringTransformer implements TransformerInterface
{
    /**
     * @var PhoneNumberFormat::*
     */
    private $format;

    /**
     * @var PhoneNumberUtil|null
     */
    private $formatter;

    /**
     * PhoneNumberToStringTransformer constructor.
     *
     * @param PhoneNumberFormat::* $format
     * @param PhoneNumberUtil|null $formatter
     */
    public function __construct(int $format = PhoneNumberFormat::E164, ?PhoneNumberUtil $formatter = null)
    {
        $this->format = $format;
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function transformToHttp($value, ElementInterface $input): ?PhoneNumber
    {
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

        return $formatter->format($value, $this->format);
    }
}
