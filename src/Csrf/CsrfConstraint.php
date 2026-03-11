<?php

namespace Bdf\Form\Csrf;

use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * @internal
 */
class CsrfConstraint extends Constraint
{
    const INVALID_TOKEN_ERROR = 'cd108896-d12a-4455-a6cc-ba13708c8e7f';

    protected const ERROR_NAMES = [self::INVALID_TOKEN_ERROR => 'INVALID_TOKEN_ERROR'];

    /**
     * The constraint message
     *
     * @var string
     */
    public string $message = 'The CSRF token is invalid.';
    public CsrfTokenManagerInterface $manager;

    public function __construct(CsrfTokenManagerInterface $manager, ?string $message = null)
    {
        parent::__construct();

        $this->manager = $manager;
        $this->message = $message ?? $this->message;
    }
}
