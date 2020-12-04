<?php

namespace Bdf\Form\Csrf;

use BadMethodCallException;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use LogicException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Builder for a CsrfElement
 *
 * @see CsrfElement
 */
class CsrfElementBuilder implements ElementBuilderInterface
{
    /**
     * @var string
     */
    private $tokenId = CsrfElement::class;

    /**
     * @var string|null
     */
    private $message = null;

    /**
     * @var bool
     */
    private $invalidate = false;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * CsrfElementBuilder constructor.
     */
    public function __construct()
    {
        if (!interface_exists(CsrfTokenManagerInterface::class)) {
            throw new LogicException(CsrfTokenManagerInterface::class.' cannot be found. The package "symfony/security-csrf" must be installed for use the CsrfElement');
        }
    }

    /**
     * Define the CSRF token id
     * By default, the token id is CsrfElement class name
     *
     * @param string $tokenId
     *
     * @return $this
     */
    public function tokenId(string $tokenId): self
    {
        $this->tokenId = $tokenId;

        return $this;
    }

    /**
     * Define the error message
     *
     * @param string|null $message
     *
     * @return $this
     */
    public function message(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Does the token should be invalidated after check ?
     *
     * @param bool $invalidate If true, the token is for one use, if false, the token can b reused
     *
     * @return $this
     */
    public function invalidate(bool $invalidate = true): self
    {
        $this->invalidate = $invalidate;

        return $this;
    }

    /**
     * Define the CSRF token manager
     *
     * @param CsrfTokenManagerInterface $tokenManager
     *
     * @return $this
     */
    public function tokenManager(CsrfTokenManagerInterface $tokenManager): self
    {
        $this->tokenManager = $tokenManager;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function satisfy($constraint, $options = null, $append = true)
    {
        throw new BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function transformer($transformer, $append = true)
    {
        throw new BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function value($value)
    {
        throw new BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function buildElement(): ElementInterface
    {
        return new CsrfElement(
            $this->tokenId,
            new CsrfValueValidator($this->invalidate, $this->message ? ['message' => $this->message] : []),
            $this->tokenManager
        );
    }
}
