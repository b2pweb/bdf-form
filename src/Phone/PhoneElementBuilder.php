<?php

namespace Bdf\Form\Phone;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\ElementInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\RegionCode;
use Symfony\Component\Validator\Constraint;

/**
 * Builder for a phone element
 *
 * @see PhoneElement
 */
class PhoneElementBuilder extends AbstractElementBuilder
{
    /**
     * @var callable
     */
    private $regionResolver;

    /**
     * @var PhoneNumberUtil
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
     * Option for phone number validation
     *
     * @var array
     */
    private $validPhoneNumberConstraintOptions = [];


    /**
     * PhoneElementBuilder constructor.
     * @param RegistryInterface|null $registry
     */
    public function __construct(RegistryInterface $registry = null)
    {
        parent::__construct($registry);

        $this->addConstraintsProvider([$this, 'providePhoneConstraint']);
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
     * @param callable $regionResolver The resolver. Takes as parameter the PhoneElement, and must return the country code as string
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
     * @param string $inputName The input name. It should be located on the same parent element, and returns the region code
     *
     * @return $this
     *
     * @see RegionCode
     */
    public function regionInput(string $inputName): self
    {
        return $this->regionResolver(function (ElementInterface $element) use($inputName) { return $element->container()->parent()[$inputName]->element()->value(); });
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
     * @param array|string $options The option array, or string for provide the error message
     *
     * @return $this
     * @see ValidPhoneNumber
     */
    public function validateNumber($options =  []): self
    {
        if (is_string($options)) {
            $options = ['message' => $options];
        }

        $this->allowInvalidNumber = false;
        $this->validPhoneNumberConstraintOptions = $options;

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
    protected function providePhoneConstraint(): array
    {
        if ($this->allowInvalidNumber) {
            return [];
        }

        return [new ValidPhoneNumber($this->validPhoneNumberConstraintOptions)];
    }
}
