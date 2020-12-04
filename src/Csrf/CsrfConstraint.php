<?php

namespace Bdf\Form\Csrf;

use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * @internal
 */
class CsrfConstraint extends Constraint
{
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

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'manager';
    }
}
