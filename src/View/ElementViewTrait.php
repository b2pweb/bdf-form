<?php

namespace Bdf\Form\View;

use function is_string;

/**
 * Implements @see ElementViewInterface
 *
 * @psalm-require-implements ElementViewInterface
 */
trait ElementViewTrait
{
    /**
     * @var string
     */
    private string $type;

    /**
     * @var string|null
     */
    private ?string $error = null;

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function setError(?string $error): static
    {
        $this->error = $error;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function onError(string|callable $action): ?string
    {
        if (!$this->hasError()) {
            return null;
        }

        return is_string($action) ? $action : $action($this);
    }
}
