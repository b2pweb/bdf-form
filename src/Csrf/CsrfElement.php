<?php

namespace Bdf\Form\Csrf;

use BadMethodCallException;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Leaf\LeafRootElement;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Util\ContainerTrait;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Class CsrfElement
 */
final class CsrfElement implements ElementInterface
{
    use ContainerTrait;

    /**
     * @var string
     */
    private $tokenId;

    /**
     * @var CsrfValueValidator
     */
    private $validator;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var CsrfToken|null
     */
    private $value = null;

    /**
     * @var FormError
     */
    private $error;

    /**
     * CsrfElement constructor.
     *
     * @param string $tokenId
     * @param CsrfValueValidator|null $validator
     * @param CsrfTokenManagerInterface $tokenManager
     */
    public function __construct(?string $tokenId = null, ?CsrfValueValidator $validator = null, ?CsrfTokenManagerInterface $tokenManager = null)
    {
        $this->tokenId = $tokenId ?: self::class;
        $this->validator = $validator ?: new CsrfValueValidator();
        $this->tokenManager = $tokenManager ?: new CsrfTokenManager();

        $this->error = FormError::null();
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): ElementInterface
    {
        $this->value = new CsrfToken($this->tokenId, $data);
        $this->error = $this->validator->validate($this->value, $this);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($entity): ElementInterface
    {
        throw new BadMethodCallException('Cannot set a Csrf token value');
    }

    /**
     * {@inheritdoc}
     */
    public function value(): CsrfToken
    {
        if ($this->value) {
            return $this->value;
        }

        return $this->value = $this->tokenManager->getToken($this->tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function httpValue(): string
    {
        return $this->value()->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->value && $this->error->empty();
    }

    /**
     * {@inheritdoc}
     */
    public function error(): FormError
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function root(): RootElementInterface
    {
        return $this->container() ? $this->container()->parent()->root() : new LeafRootElement($this);
    }

    /**
     * {@inheritdoc}
     */
    public function view()
    {
        // TODO: Implement view() method.
    }

    /**
     * @return CsrfTokenManagerInterface
     * @internal Used by the validator
     */
    public function getTokenManager(): CsrfTokenManagerInterface
    {
        return $this->tokenManager;
    }

    /**
     * Invalidate the current CSRF token
     * After this call, the CSRF cannot be valid anymore
     */
    public function invalidateToken(): void
    {
        $this->tokenManager->removeToken($this->tokenId);
    }
}
