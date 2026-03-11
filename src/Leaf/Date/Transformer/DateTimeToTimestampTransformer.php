<?php

namespace Bdf\Form\Leaf\Date\Transformer;

use Attribute;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Transformer\TransformerInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Override;

use function assert;
use function method_exists;

/**
 * Transform a DateTime instance from a form element to a timestamp to a model
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class DateTimeToTimestampTransformer implements TransformerInterface
{
    /**
     * @var class-string<DateTimeInterface>|null
     */
    private ?string $className;

    /**
     * @var DateTimeZone|null
     */
    private ?DateTimeZone $timezone;


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

    #[Override]
    public function transformToHttp(mixed $value, ElementInterface $input): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Expected a numeric.');
        }

        $className = $this->className ?? ($input instanceof DateTimeElement ? $input->dateTimeClassName() : DateTime::class);
        $timezone = $this->timezone ?? ($input instanceof DateTimeElement ? $input->timezone() : null);

        /** @psalm-suppress UnsafeInstantiation */
        /** @var DateTimeInterface $dateTime */
        $dateTime = new $className;

        if ($timezone) {
            assert(method_exists($dateTime, 'setTimezone'));
            $dateTime = $dateTime->setTimezone($timezone);
        }

        assert(method_exists($dateTime, 'setTimestamp'));
        return $dateTime->setTimestamp($value);
    }

    #[Override]
    public function transformFromHttp($value, ElementInterface $input): ?int
    {
        if (!$value instanceof DateTimeInterface) {
            return null;
        }

        return $value->getTimestamp();
    }
}
