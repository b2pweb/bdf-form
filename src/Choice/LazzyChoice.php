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
     * @var ChoiceInterface
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
        $this->build();

        return $this->choices->values();
    }

    /**
     * {@inheritdoc}
     */
    public function view(?callable $configurator = null): array
    {
        $this->build();

        return $this->choices->view($configurator);
    }

    /**
     * Resolve the lazzy choice list
     */
    private function build(): void
    {
        if ($this->choices !== null) {
            return;
        }

        $callback = $this->resolver;
        $this->choices = $callback();

        if (!$this->choices instanceof ChoiceInterface) {
            $this->choices = new ArrayChoice((array)$this->choices);
        }
    }
}
