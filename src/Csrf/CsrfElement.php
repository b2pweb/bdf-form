<?php

namespace Bdf\Form\Csrf;

use BadMethodCallException;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Leaf\LeafRootElement;
use Bdf\Form\Leaf\View\SimpleElementView;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Util\ContainerTrait;
use Bdf\Form\View\ElementViewInterface;
use Override;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Element for add a CSRF token on the form
 * The token value is auto-generated using CsrfTokenManagerInterface
 *
 * This element is always required, and validated, and cannot be `import()'d`
 *
 * The dependency "symfony/security-csrf" is required to use this element
 *
 * @see CsrfTokenManager
 *
 * @implements ElementInterface<CsrfToken>
 */
final class CsrfElement implements ElementInterface
{
    use ContainerTrait;

    private readonly string $tokenId;
    private readonly CsrfValueValidator $validator;
    private readonly CsrfTokenManagerInterface $tokenManager;
    private ?CsrfToken $value = null;
    private FormError $error;

    /**
     * CsrfElement constructor.
     *
     * @param string|null $tokenId
     * @param CsrfValueValidator|null $validator
     * @param CsrfTokenManagerInterface|null $tokenManager
     */
    public function __construct(?string $tokenId = null, ?CsrfValueValidator $validator = null, ?CsrfTokenManagerInterface $tokenManager = null)
    {
        $this->tokenId = $tokenId ?? self::class;
        $this->validator = $validator ?? new CsrfValueValidator();
        $this->tokenManager = $tokenManager ?? new CsrfTokenManager();

        $this->error = FormError::null();
    }

    #[Override]
    public function submit(mixed $data): static
    {
        $this->value = new CsrfToken($this->tokenId, $data);
        $this->error = $this->validator->validate($this->value, $this);

        return $this;
    }

    #[Override]
    public function patch(mixed $data): static
    {
        // CSRF element must be submitted
        return $this->submit($data);
    }

    #[Override]
    public function import(mixed $entity): static
    {
        throw new BadMethodCallException('Cannot set a Csrf token value');
    }

    #[Override]
    public function value(): CsrfToken
    {
        if ($this->value) {
            return $this->value;
        }

        return $this->value = $this->tokenManager->getToken($this->tokenId);
    }

    #[Override]
    public function httpValue(): string
    {
        return $this->value()->getValue();
    }

    #[Override]
    public function valid(): bool
    {
        return $this->value && $this->error->empty();
    }

    #[Override]
    public function failed(): bool
    {
        return !$this->valid();
    }

    #[Override]
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $field ? $this->error->withField($field) : $this->error;
    }

    #[Override]
    public function root(): RootElementInterface
    {
        return ($container = $this->container()) ? $container->parent()->root() : new LeafRootElement($this);
    }

    #[Override]
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        return new SimpleElementView(
            self::class,
            (string) $field,
            $this->tokenManager->getToken($this->tokenId), // Always get the real token value
            $this->error->global(),
            true, // Token is always required
            []
        );
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
