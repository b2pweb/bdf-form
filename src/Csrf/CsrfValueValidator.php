<?php

namespace Bdf\Form\Csrf;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;

/**
 * Class CsrfValueValidator
 */
final class CsrfValueValidator implements ValueValidatorInterface
{
    /**
     * Invalidate the token after verification ?
     *
     * @var boolean
     */
    private $invalidate;

    /**
     * The constraint options
     *
     * @var array
     */
    private $options;

    /**
     * CsrfValueValidator constructor.
     *
     * @param bool $invalidate
     * @param array $options
     */
    public function __construct(bool $invalidate = false, array $options = [])
    {
        $this->invalidate = $invalidate;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @param CsrfElement $element
     */
    public function validate($value, ElementInterface $element): FormError
    {
        try {
            return (new ConstraintValueValidator(new CsrfConstraint($this->options + ['manager' => $element->getTokenManager()])))->validate($value, $element);
        } finally {
            if ($this->invalidate) {
                $element->invalidateToken();
            }
        }
    }
}
