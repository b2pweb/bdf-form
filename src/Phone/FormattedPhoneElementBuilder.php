<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Util\DelegateElementBuilderTrait;
use libphonenumber\PhoneNumberFormat;

/**
 * Build a formatted phone element
 * Delegates all builder methods to the PhoneElementBuilder
 *
 * Note: Because the built element simply decorate a PhoneElement, all validation and transformation are performed by this
 *       element, so, it'll take as parameter a PhoneNumber object instead of a string
 *
 * <code>
 * $builder->formattedPhone('contact')
 *     ->regionInput('address/country') // All PhoneElementBuilder methods are available
 *     ->format(PhoneNumberFormat::INTERNATIONAL) // Define the phone format for the model (i.e. PHP) value
 *     ->satisfy(function (PhoneNumber $phone) { // For adding constraints (or transformers), use PhoneNumber as paramerer
 *         // Validation
 *     })
 * ;
 * </code>
 *
 * @see FormattedPhoneElement The built element
 * @see FormBuilder::formattedPhone() The create the builder
 *
 * @implements ElementBuilderInterface<FormattedPhoneElement>
 * @mixin PhoneElementBuilder
 */
class FormattedPhoneElementBuilder implements ElementBuilderInterface
{
    use DelegateElementBuilderTrait;

    /**
     * @var PhoneElementBuilder
     */
    private $builder;

    /**
     * @var PhoneNumberFormat::*
     */
    private $format = PhoneNumberFormat::E164;

    /**
     * @var string|null
     */
    private $value;


    /**
     * FormattedPhoneElementBuilder constructor.
     *
     * @param PhoneElementBuilder $builder
     */
    public function __construct(PhoneElementBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function value($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Define the model (i.e. PHP) value format
     * The format is one of the PhoneNumberFormat::* constants
     *
     * <code>
     * $builder->formattedPhone('phone')->format(PhoneNumberFormat::INTERNATIONAL); // To handle format "+XX X XX XX XX XX"
     * $builder->formattedPhone('phone')->format(PhoneNumberFormat::NATIONAL); // To handle format "0X XX XX XX XX"
     * </code>
     *
     * @param PhoneNumberFormat::* $format The format
     *
     * @return $this
     *
     * @see PhoneNumberFormat::E164
     * @see PhoneNumberFormat::INTERNATIONAL
     * @see PhoneNumberFormat::NATIONAL
     * @see PhoneNumberFormat::RFC3966
     */
    public function format(int $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildElement(): ElementInterface
    {
        $element = new FormattedPhoneElement($this->builder->buildElement(), $this->format);

        if ($this->value !== null) {
            $element->import($this->value);
        }

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    protected function getElementBuilder(): ElementBuilderInterface
    {
        return $this->builder;
    }
}
