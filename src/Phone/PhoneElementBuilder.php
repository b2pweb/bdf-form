<?php

namespace Bdf\Form\Phone;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\FieldPath;
use Bdf\Form\Validator\ValueValidatorInterface;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\RegionCode;
use Symfony\Component\Validator\Constraint;

/**
 * Builder for a phone element
 *
 * <code>
 * $builder->phone('contact')
 *     ->depends('country')
 *     ->regionInput('country')
 *     ->allowInvalidNumber()
 * ;
 * </code>
 *
 * @see PhoneElement
 * @see FormBuilderInterface::phone()
 *
 * @extends AbstractElementBuilder<PhoneElement>
 */
class PhoneElementBuilder extends AbstractElementBuilder
{
    /**
     * @var callable(ElementInterface):string|null
     */
    private $regionResolver;

    /**
     * @var PhoneNumberUtil|null
     */
    private $formatter;

    /**
     * Invalid phone number are allowed ?
     * (i.e. number value is not validated)
     *
     * @var bool
     */
    private $allowInvalidNumber = false;

    /**
     * The error message or options for the ValidPhoneNumber constraint if the phone number is invalid
     *
     * @var string|null
     */
    private $invalidPhoneErrorMessage = null;


    /**
     * PhoneElementBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(?RegistryInterface $registry = null)
    {
        parent::__construct($registry);

        $this->addConstraintsProvider([$this, 'providePhoneConstraint']);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function required(string|Constraint|null $message = null, ?bool $allowNull = null, ?callable $normalizer = null)
    {
        if (!$message instanceof Constraint) {
            $message = new NotEmptyPhoneNumber(
                message: $message,
                allowNull: $allowNull,
                normalizer: $normalizer,
            );
        }

        return $this->satisfy($message);
    }

    /**
     * Define the region or country resolver
     *
     * <code>
     * $builder->regionResolver(function (PhoneElement $element) {
     *     return $this->user()->countryCode();
     * });
     * </code>
     *
     * @param callable(ElementInterface):string $regionResolver The resolver. Takes as parameter the PhoneElement, and must return the country code as string
     *
     * @return $this
     */
    public function regionResolver(callable $regionResolver): self
    {
        $this->regionResolver = $regionResolver;

        return $this;
    }

    /**
     * Define the default region code for parsing the phone number
     *
     * @param string $region The region code. See RegionCode constants
     *
     * @return $this
     *
     * @see RegionCode
     */
    public function region(string $region): self
    {
        return $this->regionResolver(function () use($region) { return $region; });
    }

    /**
     * Use a sibling input as region code value
     *
     * Note: Do not forget to declare the other input as dependency
     *
     * <code>
     * $builder->string('country')->choice();
     * $builder
     *      ->phone('phone')
     *      ->depends('country')
     *      ->regionInput('country')
     * ;
     * </code>
     *
     * @param string $inputPath The input path
     *
     * @return $this
     *
     * @see RegionCode
     * @see FieldPath::parse() For the path syntax
     * @see ChildBuilderInterface::depends() For declare the dependency to the other field
     */
    public function regionInput(string $inputPath): self
    {
        return $this->regionResolver(function (ElementInterface $element) use($inputPath) {
            return FieldPath::parse($inputPath)->value($element);
        });
    }

    /**
     * Define the PhoneNumberUtil instance
     *
     * @param PhoneNumberUtil $formatter
     *
     * @return $this
     */
    public function formatter(PhoneNumberUtil $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Disable phone number validation check
     * If enabled, the element will not be marked as invalid if an invalid number is submitted
     *
     * @param bool $allowInvalidNumber
     *
     * @return $this
     */
    public function allowInvalidNumber(bool $allowInvalidNumber = true): self
    {
        $this->allowInvalidNumber = $allowInvalidNumber;

        return $this;
    }

    /**
     * Define phone validation options
     *
     * Note: This method can be called multiple times, the last defined options will overrides the previous ones
     *
     * Usage:
     * <code>
     * $builder->validateNumber('My error'); // Define the error message
     * $builder->validateNumber(['message' => 'My error']); // Also accept array of options
     * </code>
     *
     * @param string|null $message The error message if the phone number is invalid
     *
     * @return $this
     * @see ValidPhoneNumber
     */
    public function validateNumber(?string $message = null): self
    {
        $this->allowInvalidNumber = false;
        $this->invalidPhoneErrorMessage = $message;

        return $this;
    }

    /**
     * Define the error message if the phone number is invalid
     *
     * @param string $message
     *
     * @return $this
     * @see ValidPhoneNumber::$message
     */
    public function errorMessage(string $message): self
    {
        $this->invalidPhoneErrorMessage = $message;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new PhoneElement($validator, $transformer, $this->regionResolver, $this->formatter);
    }

    /**
     * Provide validation constraint for the phone number
     *
     * @return Constraint[]
     */
    protected function providePhoneConstraint(RegistryInterface $registry): array
    {
        if ($this->allowInvalidNumber) {
            return [];
        }

        return [new ValidPhoneNumber($this->invalidPhoneErrorMessage)];
    }
}
