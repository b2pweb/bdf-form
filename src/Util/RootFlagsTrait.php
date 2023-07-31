<?php

namespace Bdf\Form\Util;

use Bdf\Form\RootElementInterface;

/**
 * Implements flags for root elements
 *
 * @psalm-require-implements RootElementInterface
 */
trait RootFlagsTrait
{
    /**
     * Map of flags
     *
     * @var array<string, bool>
     */
    private $flags = [];

    /**
     * {@inheritdoc}
     */
    public function set(string $flag, bool $value): void
    {
        $this->flags[$flag] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function is(string $flag): bool
    {
        return !empty($this->flags[$flag]);
    }
}
