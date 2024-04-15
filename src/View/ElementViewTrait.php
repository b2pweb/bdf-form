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
    private $type;

    /**
     * @var string|null
     */
    private $error;

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
    public function setError(?string $error): ElementViewInterface
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
    public function onError($action): ?string
    {
        if (!$this->hasError()) {
            return null;
        }

        return is_string($action) ? $action : $action($this);
    }
}
