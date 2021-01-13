<?php

namespace Bdf\Form\Choice;

/**
 * Proxy choice using a callback for generate the choices array
 */
final class LazzyChoice implements ChoiceInterface
{
    /**
     * The lazzy callback with returns choices
     *
     * @var callable
     */
    private $resolver;

    /**
     * The choice object
     *
     * @var ChoiceInterface|null
     */
    private $choices;

    /**
     * LazzyChoice constructor.
     *
     * @param callable $resolver
     */
    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function values(): array
    {
        return $this->build()->values();
    }

    /**
     * {@inheritdoc}
     */
    public function view(?callable $configurator = null): array
    {
        return $this->build()->view($configurator);
    }

    /**
     * Resolve the lazzy choice list
     */
    private function build(): ChoiceInterface
    {
        if ($this->choices !== null) {
            return $this->choices;
        }

        $callback = $this->resolver;
        $this->choices = $callback();

        if (!$this->choices instanceof ChoiceInterface) {
            $this->choices = new ArrayChoice((array)$this->choices);
        }

        return $this->choices;
    }
}
