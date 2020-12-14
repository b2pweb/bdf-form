<?php

namespace Bdf\Form\View;

/**
 * Base form element view type
 */
interface ElementViewInterface
{
    /**
     * Get the form element class name
     *
     * @return string
     */
    public function type(): string;

    /**
     * Get the current element error
     * In case of aggregate element, contains the global error and not the children errors
     *
     * @return string|null
     */
    public function error(): ?string;

    /**
     * Check if the current element is on error
     * In case of aggregate element, this method will return false if there is at least one child on error or a global error
     * So, `ElementViewInterface::error()` may return null while `ElementViewInterface::hasError()` is true
     *
     * Usage:
     * <code>
     * if ($form->hasError()) {
     *     echo '<div class="alert alert-danger">The form has errors</div>';
     * }
     * </code>
     *
     * @return bool
     */
    public function hasError(): bool;

    /**
     * Perform an action if the field is on error, and return the value
     * This is equivalent to : `$view->hasError() ? $action($view) : null;`
     *
     * Usage:
     * <code>
     * // Directly return the string
     * echo $form['element']->onError('My error message');
     *
     * // Use callback
     * echo $form['element']->onError(function ($view) { return '<div class="alert alert-danger">'.$view->error().'</div>'; });
     * </code>
     *
     * @param string|callable(ElementViewInterface):string $action The action to perform, or string to return
     *
     * @return string|null
     *
     * @see ElementViewInterface::hasError() To check if the element has an error
     */
    public function onError($action): ?string;
}
