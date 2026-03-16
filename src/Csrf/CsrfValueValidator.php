<?php

namespace Bdf\Form\Csrf;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Exception;

use Override;

use function assert;
use function method_exists;

/**
 * Class CsrfValueValidator
 *
 * @implements ValueValidatorInterface<\Symfony\Component\Security\Csrf\CsrfToken>
 */
final readonly class CsrfValueValidator implements ValueValidatorInterface
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
     */
    private bool $invalidate;

    /**
     * The error message
     */
    private ?string $message;

    /**
     * Only validate the csrf token if the element is on the root form
     * If false, all csrf tokens on sub forms will be validated
     */
    private bool $onlyValidateRoot;

    /**
     * CsrfValueValidator constructor.
     *
     * @param bool $invalidate Always invalidate the token after validation
     * @param string|null $message The error message
     * @param bool $onlyValidateRoot Only validate the csrf token if the element is on the root form
     */
    public function __construct(bool $invalidate = false, ?string $message = null, bool $onlyValidateRoot = false)
    {
        $this->invalidate = $invalidate;
        $this->message = $message;
        $this->onlyValidateRoot = $onlyValidateRoot;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function validate(mixed $value, ElementInterface $element): FormError
    {
        assert($element instanceof CsrfElement);

        $root = $element->root();

        if ($root->is(self::FLAG_DISABLE_CSRF_VALIDATION)) {
            return FormError::null();
        }

        if ($this->onlyValidateRoot && !self::belongsToRoot($element)) {
            return FormError::null();
        }

        try {
            return new ConstraintValueValidator([new CsrfConstraint($element->getTokenManager(), $this->message)])->validate($value, $element);
        } finally {
            if ($this->invalidate) {
                $element->invalidateToken();
            }
        }
    }

    #[Override]
    public function onTransformerException(Exception $exception, $value, ElementInterface $element): FormError
    {
        // Ignore transformer exception: the CSRF token will be validated after
        return FormError::null();
    }

    #[Override]
    public function constraints(): array
    {
        return []; // Does CsrfConstraint should be returns ?
    }

    #[Override]
    public function hasConstraints(): bool
    {
        return true;
    }

    /**
     * Check if the given element is the root element
     *
     * @param ElementInterface $element
     * @return bool
     */
    private static function belongsToRoot(ElementInterface $element): bool
    {
        $container = $element->container();

        return $container === null || $container->parent()->container() === null;
    }
}
