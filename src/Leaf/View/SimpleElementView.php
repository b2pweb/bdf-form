<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\Leaf\LeafElement;
use Bdf\Form\View\ElementViewTrait;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use Bdf\Form\View\FieldViewTrait;

/**
 * View for simple input fields
 *
 * @see LeafElement::view()
 *
 * @todo test serialization
 */
final class SimpleElementView implements FieldViewInterface
{
    use ElementViewTrait;
    use FieldViewTrait;

    /**
     * SimpleElementView constructor.
     *
     * @param string $type
     * @param string $name
     * @param mixed $value
     * @param string|null $error
     * @param bool $required
     * @param array $constraints
     */
    public function __construct(string $type, string $name, $value, ?string $error, bool $required, array $constraints)
    {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
        $this->error = $error;
        $this->required = $required;
        $this->constraints = $constraints;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultRenderer(): FieldViewRendererInterface
    {
        return SimpleFieldHtmlRenderer::instance();
    }
}
