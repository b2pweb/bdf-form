<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\Csrf\CsrfElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use Bdf\Form\View\HtmlRenderer;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Default render for @see SimpleElementView
 */
final class SimpleFieldHtmlRenderer implements FieldViewRendererInterface
{
    /**
     * @var SimpleFieldHtmlRenderer
     */
    private static $instance;

    /**
     * Map constraint class name to mapped attributes in form :
     * $constraintMapping[$constraintClassName][$constraintAttributeName] = $htmlAttribute
     *
     * @var string[][]
     */
    private $constraintMapping = [
        Length::class => ['min' => 'minlength', 'max' => 'maxlength'],
        LessThanOrEqual::class => ['value' => 'max'],
        GreaterThanOrEqual::class => ['value' => 'min'],
        PositiveOrZero::class => ['value' => 'min'],
    ];

    /**
     * Map element type to html5 input type
     *
     * @var string[]
     */
    private $typesMapping = [
        IntegerElement::class => 'number',
        PhoneElement::class => 'tel',
        CsrfElement::class => 'hidden',
    ];

    /**
     * {@inheritdoc}
     */
    public function render(FieldViewInterface $view, array $attributes): string
    {
        if (!isset($attributes['type'])) {
            $attributes['type'] = $this->typesMapping[$view->type()] ?? 'text';
        }

        $attributes['name'] = $view->name();
        $attributes['value'] = $view->value();
        $attributes['required'] = $view->required();
        $attributes += $this->constraintsToAttributes($view->constraints());

        return HtmlRenderer::element('input', $attributes);
    }

    private function constraintsToAttributes(array $constraints): array
    {
        $attributes = [];

        foreach ($constraints as $type => $values) {
            if (!isset($this->constraintMapping[$type])) {
                continue;
            }

            foreach ($this->constraintMapping[$type] as $from => $to) {
                if (isset($values[$from]) && $values[$from] !== '') {
                    $attributes[$to] = $values[$from];
                }
            }
        }

        return $attributes;
    }

    /**
     * Get the renderer instance
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self;
    }
}
