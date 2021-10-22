<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Leaf\LeafElement;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use TypeError;

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

        $this->regionResolver = $regionResolver;
        $this->formatter = $formatter ?? PhoneNumberUtil::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    protected function toPhp($httpValue)
    {
        if ($httpValue === null) {
            return null;
        }

        return $this->parseValue($httpValue);
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue)
    {
        if (!$phpValue) {
            return null;
        }

        return $phpValue->getRawInput() ?? $this->formatter->format($phpValue, PhoneNumberFormat::E164);
    }

    /**
     * {@inheritdoc}
     *
     * @return PhoneNumber|null
     */
    protected function tryCast($value): ?PhoneNumber
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof PhoneNumber) {
            throw new TypeError('The import()\'ed value of a '.static::class.' must be an instance of '.PhoneNumber::class.' or null');
        }

        return $value;
    }

    /**
     * Get the resolved region string
     *
     * @return string
     */
    private function resolveRegion(): string
    {
        if (!$this->regionResolver) {
            return PhoneNumberUtil::UNKNOWN_REGION;
        }

        return strtoupper(($this->regionResolver)($this));
    }

    /**
     * @internal
     */
    public function getFormatter(): PhoneNumberUtil
    {
        return $this->formatter;
    }

    /**
     * Parse a phone number string
     *
     * @param string $rawPhoneNumber The string value of the phone number
     * @return PhoneNumber The parsed instance
     *
     * @internal
     */
    public function parseValue(string $rawPhoneNumber): PhoneNumber
    {
        try {
            return $this->formatter->parse($rawPhoneNumber, $this->resolveRegion(), null, true);
        } catch (NumberParseException $e) {
            return (new PhoneNumber())->setRawInput($rawPhoneNumber);
        }
    }
}
