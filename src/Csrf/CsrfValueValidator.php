<?php

namespace Bdf\Form\Csrf;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Exception;

/**
 * Class CsrfValueValidator
 *
 * @implements ValueValidatorInterface<\Symfony\Component\Security\Csrf\CsrfToken>
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
     * @param bool $invalidate Always invalidate the token after validation
     * @param array $options Constraints options
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
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function validate($value, ElementInterface $element): FormError
    {
        try {
            return (new ConstraintValueValidator([new CsrfConstraint($this->options + ['manager' => $element->getTokenManager()])]))->validate($value, $element);
        } finally {
            if ($this->invalidate) {
                $element->invalidateToken();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onTransformerException(Exception $exception, $value, ElementInterface $element): FormError
    {
        // Ignore transformer exception: the CSRF token will be validated after
        return FormError::null();
    }

    /**
     * {@inheritdoc}
     */
    public function constraints(): array
    {
        return []; // Does CsrfConstraint should be returns ?
    }

    /**
     * {@inheritdoc}
     */
    public function hasConstraints(): bool
    {
        return true;
    }
}
