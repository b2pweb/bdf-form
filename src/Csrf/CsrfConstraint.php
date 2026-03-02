<?php

namespace Bdf\Form\Csrf;

use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraint;

use function is_array;
use function sprintf;
use function trigger_error;

/**
 * @internal
 */
class CsrfConstraint extends Constraint
{
    const INVALID_TOKEN_ERROR = 'cd108896-d12a-4455-a6cc-ba13708c8e7f';

    protected const ERROR_NAMES = [self::INVALID_TOKEN_ERROR => 'INVALID_TOKEN_ERROR'];
    protected static $errorNames = self::ERROR_NAMES;

    /**
     * The constraint message
     *
     * @var string
     */
    public $message = 'The CSRF token is invalid.';

    /**
     * @var CsrfTokenManagerInterface
     */
    public $manager;

    public function __construct($manager = null, ?string $message = null)
    {
        if (is_array($manager)) {
            @trigger_error(sprintf('Passing an array of options on %s is deprecated since 1.7 and will be removed on 2.0, use named arguments instead.', __METHOD__), E_USER_DEPRECATED);
            $options = $manager;
            $manager = $options['manager'] ?? null;
            $message = $message ?? $options['message'] ?? null;
        }

        parent::__construct($options ?? null);

        $this->manager = $manager ?? $this->manager;
        $this->message = $message ?? $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): ?string
    {
        return 'manager';
    }
}
