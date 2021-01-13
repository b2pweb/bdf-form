<?php

namespace Bdf\Form\Leaf\Date;

use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Leaf\LeafElement;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Handle DateTime form element
 * The element use a formatted string as http value, and can return any implementation of DateTimeInterface
 *
 * @method DateTimeInterface value()
 */
final class DateTimeElement extends LeafElement
{
    /**
     * @var class-string<DateTimeInterface>
     */
    private $className;

    /**
     * @var string
     */
    private $format;

    /**
     * @var DateTimeZone|null
     */
    private $timezone;

    /**
     * DateTimeType constructor.
     *
     * @param ValueValidatorInterface|null $validator
     * @param TransformerInterface|null $transformer
     * @param ChoiceInterface|null $choices
     * @param class-string<DateTimeInterface> $className The date time class name to use
     * @param string $format The time format string
     * @param DateTimeZone|null $timezone Timezone to use. Use null to not define a timezone
     */
    public function __construct(?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null, ?ChoiceInterface $choices = null, string $className = DateTime::class, string $format = DateTime::ATOM, ?DateTimeZone $timezone = null)
    {
        parent::__construct($validator, $transformer, $choices);

        $this->className = $className;
        $this->format = $format;
        $this->timezone = $timezone;
    }

    /**
     * {@inheritdoc}
     */
    protected function toPhp($httpValue): ?DateTimeInterface
    {
        if ($httpValue === null) {
            return null;
        }

        switch (true) {
            case $httpValue instanceof $this->className:
                $dateTime = $httpValue; // Clone ?
                break;

            case $httpValue instanceof DateTimeInterface:
                $httpValue = $httpValue->format($this->format);
                // No break

            default:
                if (!method_exists($this->className, 'createFromFormat')) {
                    throw new \LogicException('Invalid DateTime class name "'.$this->className.'" : method createFromFormat() is not found.');
                }

                $dateTime = ($this->className)::createFromFormat($this->format, $httpValue, $this->timezone);
        }

        if ($dateTime === false) {
            throw new InvalidArgumentException('Invalid date format');
        }

        if ($this->timezone !== null) {
            $dateTime->setTimezone($this->timezone);
        }

        return $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue)
    {
        if (!$phpValue instanceof DateTimeInterface) {
            return null;
        }

        return $phpValue->format($this->format);
    }
}
