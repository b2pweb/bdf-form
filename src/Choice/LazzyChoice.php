<?php

namespace Bdf\Form\Choice;

/**
 * Proxy choice using a callback for generate the choices array
 *
 * @template T
 * @implements ChoiceInterface<T>
 */
final class LazzyChoice implements ChoiceInterface
{
    /**
     * The lazzy callback with returns choices
     *
     * @var callable():(T[]|ChoiceInterface<T>)
     */
    private $resolver;

    /**
     * The choice object
     *
     * @var ChoiceInterface<T>|null
     */
    private $choices;

    /**
     * LazzyChoice constructor.
     *
     * @param callable():(T[]|ChoiceInterface<T>) $resolver
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

        $choices = ($this->resolver)();

        if (!$choices instanceof ChoiceInterface) {
            $choices = new ArrayChoice((array) $choices);
        }

        return $this->choices = $choices;
    }
}
