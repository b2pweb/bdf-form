<?php

namespace Bdf\Form\Choice;

/**
 * Choice container
 * Store possible values for an element
 *
 * @template T
 */
interface ChoiceInterface
{
    /**
     * Get the available PHP values
     *
     * @return T[]
     */
    public function values(): array;

    /**
     * Get the available values for view display
     *
     * <code>
     * $choices->view(function ($view) {
     *     $view->setSelected($view->value() == $this->value);
     *     $view->setValue($this->transformToHttp($view->value()));
     * });
     * </code>
     *
     * @param callable(ChoiceView):void|null $configuration The view configuration callback
     *
     * @return ChoiceView[]
     */
    public function view(?callable $configuration = null): array;
}
