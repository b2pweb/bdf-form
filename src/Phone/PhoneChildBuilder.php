<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Phone\Transformer\PhoneNumberToStringTransformer;
use Bdf\Form\Registry\RegistryInterface;
use libphonenumber\PhoneNumberFormat;

/**
 * @extends ChildBuilder<PhoneElementBuilder>
 */
class PhoneChildBuilder extends ChildBuilder
{
    /**
     * @var PhoneNumberFormat::*|null
     */
    private $saveFormat;

    /**
     * {@inheritdoc}
     *
     * @param PhoneElementBuilder $elementBuilder
     */
    public function __construct(string $name, ElementBuilderInterface $elementBuilder, RegistryInterface $registry = null)
    {
        parent::__construct($name, $elementBuilder, $registry);

        $this->addTransformerProvider([$this, 'provideModelTransformer']);
    }

    /**
     * The model value of the input will be transformer to a formatted string
     *
     * <code>
     * // The entity : the phone is a simple string
     * class MyEntity {
     *     public string $phone;
     * }
     *
     * // Build the element
     * $builder->phone('phone')->saveAsString()->getter()->setter();
     *
     * $form->import(MyEntity::get($id));
     * $form['phone']->element()->value(); // Value is an instance of PhoneNumber
     *
     * $entity = $form->value();
     * $entity->phone; // phone is a string
     * </code>
     *
     * @param PhoneNumberFormat::*|null $format The phone number format. Must be one of the constant of PhoneNumberFormat. Set null to disable
     *
     * @return $this
     *
     * @see PhoneNumberToStringTransformer
     */
    public function saveAsString(?int $format = PhoneNumberFormat::E164): self
    {
        $this->saveFormat = $format;

        return $this;
    }

    /**
     * @return PhoneNumberToStringTransformer[]
     */
    protected function provideModelTransformer(): array
    {
        if ($this->saveFormat === null) {
            return [];
        }

        return [new PhoneNumberToStringTransformer($this->saveFormat)];
    }
}
