<?php

namespace Bdf\Form\Csrf;

use BadMethodCallException;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use LogicException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Builder for a CsrfElement
 *
 * Unlike other elements, some methods are disallowed, due to the CSRF implementation :
 * - satisfy() : The only performed validation is the CSRF token check
 * - transformer() : Not implemented
 * - value() : `import()` value is disabled on the element, and token value is always provided on the view
 * - default() : A token must be provided by the HTTP request
 *
 * <code>
 * $builder->csrf()->message('invalid token')->invalidate();
 * </code>
 *
 * @see CsrfElement
 * @see FormBuilderInterface::csrf()
 *
 * @implements ElementBuilderInterface<CsrfElement>
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
     * Only validate the csrf token if the element is on the root form
     * If false, all csrf tokens on sub forms will be validated
     *
     * @var bool
     */
    private $onlyValidateRoot = true;

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
     *
     * @see CsrfTokenManagerInterface::getToken() The parameter tokenId will be used as parameter of this method
     */
    public function tokenId(string $tokenId): self
    {
        $this->tokenId = $tokenId;

        return $this;
    }

    /**
     * Define the error message
     *
     * @param string|null $message The message, or null to use the default one
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
     * @param bool $invalidate If true, the token is for one use, if false, the token can be reused
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
     * Enable or disable the validation of the csrf token on sub forms
     * By default, the csrf token is validated only on the root form
     *
     * Note: The CSRF element and value will always be generated, even if the validation is disabled
     *
     * @param bool $validateOnSubForms If true, the csrf token will be validated on sub forms
     *
     * @return $this
     */
    public function validateOnSubForms(bool $validateOnSubForms = true): self
    {
        $this->onlyValidateRoot = !$validateOnSubForms;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function satisfy($constraint, $options = null, bool $append = true)
    {
        throw new BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function transformer($transformer, bool $append = true)
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
            new CsrfValueValidator($this->invalidate, $this->message ? ['message' => $this->message] : [], $this->onlyValidateRoot),
            $this->tokenManager
        );
    }
}
