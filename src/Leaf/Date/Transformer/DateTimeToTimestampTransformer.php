<?php

namespace Bdf\Form\Leaf\Date\Transformer;

use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Transformer\TransformerInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Transform a DateTime instance from a form element to a timestamp to a model
 */
final class DateTimeToTimestampTransformer implements TransformerInterface
{
    /**
     * @var class-string<DateTimeInterface>|null
     */
    private $className;

    /**
     * @var DateTimeZone|null
     */
    private $timezone;


    /**
     * DateTimeToTimestampTransformer constructor.
     *
     * @param class-string<DateTimeInterface>|null $className The date time class name to use when retrieving value from model. If null, will use the class defined in the input element
     * @param DateTimeZone|null $timezone The timezone to set when retrieving value from model. If null will use the element's timezone
     */
    public function __construct(?string $className = null, ?DateTimeZone $timezone = null)
    {
        $this->className = $className;
        $this->timezone = $timezone;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress UndefinedInterfaceMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function transformToHttp($value, ElementInterface $input): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Expected a numeric.');
        }

        $className = $this->className ?? ($input instanceof DateTimeElement ? $input->dateTimeClassName() : DateTime::class);
        $timezone = $this->timezone ?? ($input instanceof DateTimeElement ? $input->timezone() : null);

        /** @var DateTimeInterface $dateTime */
        $dateTime = new $className;

        if ($timezone) {
            $dateTime = $dateTime->setTimezone($timezone);
        }

        return $dateTime->setTimestamp($value);
    }

    /**
     * {@inheritdoc}
     */
    public function transformFromHttp($value, ElementInterface $input): ?int
    {
        if (!$value instanceof DateTimeInterface) {
            return null;
        }

        return $value->getTimestamp();
    }
}
