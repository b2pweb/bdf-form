<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\View\ElementViewTrait;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use Bdf\Form\View\FieldViewTrait;

/**
 * Element view for boolean / checkbox
 *
 * @see BooleanElement::view()
 */
final class BooleanElementView implements FieldViewInterface
{
    use ElementViewTrait;
    use FieldViewTrait;

    /**
     * @var string
     */
    private $httpValue;

    /**
     * @var bool
     */
    private $checked;

    /**
     * BooleanElementView constructor.
     *
     * @param string $type
     * @param string $name
     * @param mixed $value
     * @param string $httpValue
     * @param bool $checked
     * @param string|null $error
     */
    public function __construct(string $type, string $name, $value, string $httpValue, bool $checked, ?string $error)
    {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
        $this->httpValue = $httpValue;
        $this->checked = $checked;
        $this->error = $error;
    }

    /**
     * Get the HTTP value which is used as "true" for the form element
     * Unlike `FieldViewInterface::value()`, this method will always return the value even if the element is not submitted (i.e. false)
     *
     * @return string
     */
    public function httpValue(): string
    {
        return $this->httpValue;
    }

    /**
     * Does the current element is checked or submitted (i.e. it's value is true)
     * Use this method instead of value() for render a checkbox
     *
     * @return bool
     */
    public function checked(): bool
    {
        return $this->checked;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultRenderer(): FieldViewRendererInterface
    {
        return CheckboxHtmlRenderer::instance();
    }
}
