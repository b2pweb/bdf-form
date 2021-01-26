<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Leaf\LeafRootElement;
use Bdf\Form\RootElementInterface;
use Bdf\Form\View\ElementViewInterface;
use libphonenumber\PhoneNumber;

/**
 * Decorate a PhoneElement to handle string phone number instead of instance of PhoneNumber
 *
 * @see FormattedPhoneElementBuilder For build the element
 * @see PhoneElement The decored element
 */
class FormattedPhoneElement implements ElementInterface
{
    /**
     * @var PhoneElement
     * @readonly
     */
    private $element;

    /**
     * @var int
     * @readonly
     */
    private $format;

    /**
     * @var ChildInterface|null
     */
    private $container;

    /**
     * FormattedPhoneElement constructor.
     *
     * @param PhoneElement $element
     * @param \libphonenumber\PhoneNumberFormat::* $format The phone format. Must be one of the constant PhoneNumberFormat::*
     */
    public function __construct(PhoneElement $element, int $format)
    {
        $this->element = $element;
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): ElementInterface
    {
        $this->element->submit($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function patch($data): ElementInterface
    {
        $this->element->patch($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($entity): ElementInterface
    {
        $this->element->import($entity !== null ? $this->element->parseValue($entity) : null);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value(): ?string
    {
        $phone = $this->element->value();

        if (!$phone instanceof PhoneNumber) {
            return $phone;
        }

        return $this->element->getFormatter()->format($phone, $this->format);
    }

    /**
     * {@inheritdoc}
     */
    public function httpValue()
    {
        return $this->element->httpValue();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->element->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $this->element->error($field);
    }

    /**
     * {@inheritdoc}
     */
    public function container(): ?ChildInterface
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ChildInterface $container): ElementInterface
    {
        $newElement = clone $this;

        $newElement->element = $newElement->element->setContainer($container);
        $newElement->container = $container;

        return $newElement;
    }

    /**
     * {@inheritdoc}
     */
    public function root(): RootElementInterface
    {
        // @todo save the root ?
        if (!$this->container) {
            return new LeafRootElement($this);
        }

        return $this->container->parent()->root();
    }

    /**
     * {@inheritdoc}
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        return $this->element->view($field);
    }
}
