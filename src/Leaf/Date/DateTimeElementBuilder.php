<?php

namespace Bdf\Form\Leaf\Date;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\Constraint\GreaterThanField;
use Bdf\Form\Constraint\GreaterThanOrEqualField;
use Bdf\Form\Constraint\LessThanField;
use Bdf\Form\Constraint\LessThanOrEqualField;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\FieldPath;
use Bdf\Form\Validator\ValueValidatorInterface;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

/**
 * Builder for @see DateTimeElement
 *
 * Usage:
 * <code>
 * $builder
 *     ->immutable() // Use DateTimeImmutable type
 *     ->format('d/m/Y H:i') // Define the format
 *     ->timezone('Europe/Paris') // Date is on Europe/Paris timezone
 *     ->after(new DateTime()) // Must be in the future
 * ;
 * </code>
 *
 * @see DateTimeElement
 * @see FormBuilderInterface::dateTime()
 *
 * @extends AbstractElementBuilder<DateTimeElement>
 */
class DateTimeElementBuilder extends AbstractElementBuilder
{
    use ChoiceBuilderTrait;

    /**
     * @var class-string<DateTimeInterface>
     */
    private $dateTimeClassName = DateTime::class;

    /**
     * @var string
     */
    private $dateFormat = DateTime::ATOM;

    /**
     * @var DateTimeZone|null
     */
    private $timezone;

    /**
     * Define the date time class name to use
     *
     * <code>
     * $builder->dateTime('eventDate')->className(Carbon::class); // Use Carbon date time
     * </code>
     *
     * @param class-string<DateTimeInterface> $dateTimeClassName The class name. Must be an implementation of DateTimeInterface
     *
     * @return $this
     *
     * @see DateTimeElementBuilder::immutable() For use DateTimeImmutable
     */
    public function className(string $dateTimeClassName): self
    {
        $this->dateTimeClassName = $dateTimeClassName;

        return $this;
    }

    /**
     * Use the DateTimeImmutable implementation
     * This method is same as calling `$builder->className(DateTimeImmutable::class)`
     *
     * @return $this
     *
     * @see DateTimeImmutable
     * @see DateTimeElementBuilder::className()
     */
    public function immutable(): self
    {
        return $this->className(DateTimeImmutable::class);
    }

    /**
     * Define the date format to use
     * Can be one of the `DateTimeInterface` constants
     *
     * <code>
     * $builder->dateTime('birthDate')->format('d/m/Y H:i');
     * </code>
     *
     * @param string $format The format to use
     *
     * @return $this
     *
     * @see https://www.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters For the format
     */
    public function format(string $format): self
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Define the used timezone
     * - If the timezone is included in the format, the date will be converted to the given timezone (and not the input one)
     * - Else, the timezone will be used for parse the date
     *
     * <code>
     * $builder->dateTime('eventDate')->timezone('Europe/Paris');
     * $builder->dateTime('eventDate')->timezone(new DateTimeZone('+0200');
     * </code>
     *
     * @param string|DateTimeZone|null $timezone The timezone. If string is given, a new DateTimeZone will be created.
     *
     * @return $this
     */
    public function timezone($timezone): self
    {
        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Define that the element date must be before the given date
     *
     * <code>
     * $builder->dateTime('birthDate')->before(new DateTime(), 'The date must be in the past');
     * </code>
     *
     * @param DateTimeInterface $dateTime Date to compare
     * @param string|null $message The error message
     * @param bool $orEqual Does the element date can be equal ?
     *
     * @return $this
     */
    public function before(DateTimeInterface $dateTime, ?string $message = null, bool $orEqual = false): self
    {
        $constraint = $orEqual ? new LessThanOrEqual($dateTime) : new LessThan($dateTime);

        if ($message) {
            $constraint->message = $message;
        }

        return $this->satisfy($constraint);
    }

    /**
     * Define that the element date must be before the date of the other field
     *
     * Note: The current field must depends of the comparison field.
     *
     * <code>
     * $builder
     *     ->dateTime('dateStart')
     *     ->depends('dateEnd')
     *     ->beforeField('dateEnd')
     * ;
     * </code>
     *
     * @param string $field Other field to compare with. This is a field path
     * @param string|null $message The error message
     * @param bool $orEqual Does the element date can be equal ?
     *
     * @return $this
     *
     * @see FieldPath::parse() For the field path syntax
     */
    public function beforeField(string $field, ?string $message = null, bool $orEqual = false): self
    {
        $constraint = $orEqual ? new LessThanOrEqualField($field) : new LessThanField($field);

        if ($message) {
            $constraint->message = $message;
        }

        return $this->satisfy($constraint);
    }

    /**
     * Define that the element date must be after the given date
     *
     * <code>
     * $builder->dateTime('eventDate')->after(new DateTime(), 'The date must be in the future', true);
     * </code>
     *
     * @param DateTimeInterface $dateTime Date to compare
     * @param string|null $message The error message
     * @param bool $orEqual Does the element date can be equal ?
     *
     * @return $this
     */
    public function after(DateTimeInterface $dateTime, ?string $message = null, bool $orEqual = false): self
    {
        $constraint = $orEqual ? new GreaterThanOrEqual($dateTime) : new GreaterThan($dateTime);

        if ($message) {
            $constraint->message = $message;
        }

        return $this->satisfy($constraint);
    }

    /**
     * Define that the element date must be after the date of the other field
     *
     * Note: The current field must depends of the comparison field.
     *
     * <code>
     * $builder
     *     ->dateTime('dateEnd')
     *     ->depends('dateStart')
     *     ->afterField('dateStart')
     * ;
     * </code>
     *
     * @param string $field Other field to compare with. This is a field path
     * @param string|null $message The error message
     * @param bool $orEqual Does the element date can be equal ?
     *
     * @return $this
     *
     * @see FieldPath::parse() For the field path syntax
     */
    public function afterField(string $field, ?string $message = null, bool $orEqual = false): self
    {
        $constraint = $orEqual ? new GreaterThanOrEqualField($field) : new GreaterThanField($field);

        if ($message) {
            $constraint->message = $message;
        }

        return $this->satisfy($constraint);
    }

    /**
     * {@inheritdoc}
     *
     * @return DateTimeElement
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new DateTimeElement($validator, $transformer, $this->getChoices(), $this->dateTimeClassName, $this->dateFormat, $this->timezone);
    }
}
