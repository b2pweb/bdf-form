<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Leaf\LeafElement;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Element for handle phone number input
 *
 * The package "giggsey/libphonenumber-for-php" is required for use this element
 * The PHP value of this element is a PhoneNumber instance. Constraints and hydrated entities should support this type.
 *
 * @method PhoneNumber value()
 * @extends LeafElement<PhoneNumber>
 */
final class PhoneElement extends LeafElement
{
    /**
     * @var callable(PhoneElement):string
     */
    private $regionResolver;

    /**
     * @var PhoneNumberUtil
     */
    private $formatter;


    /**
     * PhoneElement constructor.
     *
     * @param ValueValidatorInterface|null $validator
     * @param TransformerInterface|null $transformer
     * @param callable|null $regionResolver Resolve the region / country code. Takes as parameter the element, and must return the country code as string
     * @param PhoneNumberUtil|null $formatter The phone number formatter
     */
    public function __construct(?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null, ?callable $regionResolver = null, ?PhoneNumberUtil $formatter = null)
    {
        parent::__construct($validator, $transformer);

        $this->regionResolver = $regionResolver ?? function(): string { return PhoneNumberUtil::UNKNOWN_REGION; };
        $this->formatter = $formatter ?? PhoneNumberUtil::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    protected function toPhp($httpValue)
    {
        if (empty($httpValue)) {
            return null;
        }

        try {
            return $this->formatter->parse($httpValue, strtoupper(($this->regionResolver)($this)), null, true);
        } catch (NumberParseException $e) {
            return (new PhoneNumber())->setRawInput($httpValue);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue)
    {
        if (!$phpValue) {
            return null;
        }

        return $phpValue->getRawInput() ?: $this->formatter->format($phpValue, PhoneNumberFormat::E164);
    }
}
