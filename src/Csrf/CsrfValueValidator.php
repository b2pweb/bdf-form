<?php

namespace Bdf\Form\Csrf;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Exception;

use function method_exists;

/**
 * Class CsrfValueValidator
 *
 * @implements ValueValidatorInterface<\Symfony\Component\Security\Csrf\CsrfToken>
 */
final class CsrfValueValidator implements ValueValidatorInterface
{
    /**
     * Flag for disable the CSRF validation
     *
     * Use this flag on the root form to disable the CSRF validation
     * Note: The CSRF token will be still generated, and the element will be still present on the form
     *
     * @see RootElementInterface::set() For define the flag
     * @see RootElementInterface::is() For check the flag
     */
    public const FLAG_DISABLE_CSRF_VALIDATION = 'disable_csrf_validation';

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
        $root = $element->root();

        if (method_exists($root, 'is') && $root->is(self::FLAG_DISABLE_CSRF_VALIDATION)) {
            return FormError::null();
        }

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
