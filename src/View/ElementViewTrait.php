<?php

namespace Bdf\Form\View;

/**
 * Implements @see ElementViewInterface
 */
trait ElementViewTrait
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
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
