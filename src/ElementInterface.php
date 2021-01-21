<?php

namespace Bdf\Form;

use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Error\FormError;
use Bdf\Form\View\ElementViewInterface;

/**
 * Base type for the form tree
 *
 * Usage:
 * <code>
 *  <div class="input-group<?php echo $input->onError(' has-error'); ?>">
 *      <label for="my-element">My label</label>
 *      <?php echo $input->id('my-element')->class('form-control'); ?>
 *      <div class="form-control-feedback"><?php echo $input->error(); ?></div>
 *  </div>
 * </code>
 *
 * @template T
 */
interface ElementInterface
{
    /**
     * Submit HTTP data to the form element
     * The value will be transformed to PHP value and validated by the element's constraints
     *
     * Usage:
     * <code>
     * // Submit and check if the input is valid
     * if (!$element->submit($request->post())->valid()) {
     *     throw new FormError($element->error());
     * }
     *
     * $value = $element->value(); // Get the transformed value
     * </code>
     *
     * @param mixed $data The HTTP data. A scalar value for a leaf element, or an array for form / aggregate element.
     *
     * @return $this
     *
     * @see ElementInterface::import() For import PHP value
     * @see ElementInterface::valid() For validates the element after submition
     * @see ElementInterface::patch() For submit value without overrides previous ones
     */
    public function submit($data): self;

    /**
     * Submit HTTP data without override previous ones
     * Like `ElementInterface::submit()`, the value will be transformed to PHP value and will be validated
     *
     * Permit to handle HTTP PATCH method when used like :
     * <code>
     * $entity = $element->import($entity)->patch($request->post())->value();
     * $repository->save($entity);
     * </code>
     *
     * On a leaf element, the value will be kept when null is passed, otherwise it will be overridden (like `submit()`).
     * On an aggregate element, patch will be called on each children.
     *
     * Note: the element value will always be revalidated when calling patch even if null if passed
     *
     * @param mixed $data The HTTP data. A scalar value for a leaf element, or an array for form / aggregate element. If null, the previous value will be kept
     *
     * @return $this
     *
     * @see ElementInterface::submit() For submit data with override old values
     */
    public function patch($data): self;

    /**
     * Set the PHP value of the element
     *
     * On an aggregate / form element, will import entity on each children elements
     * Once imported, the element value must be attached to the element : `$element->import($value)->value() === $value` must be true
     *
     * <code>
     * // Get the edit user form view
     * $userFormView = $userForm->import($user)->view();
     * </code>
     *
     * @param T|null $entity
     *
     * @return $this
     *
     * @see ElementInterface::submit() For import HTTP data
     */
    public function import($entity): self;

    /**
     * Get the element's value
     * The returned value is the transformed one, and not the raw HTTP value
     * In case of form element without attached entity, a new entity is generates and returned
     *
     * @return T|null
     *
     * @see ElementInterface::httpValue() For get the raw HTTP value
     */
    public function value();

    /**
     * Get the raw HTTP value
     * This value should be equals with submit()'ed data : `$element->submit($value)->httpValue() == $value` should be true
     *
     * Note: The value is generated from the PHP value, which may add some transformations of the original HTTP data
     *
     * @return mixed
     *
     * @see ElementInterface::value() For get the PHP value
     */
    public function httpValue();

    /**
     * Validates the element value
     * On aggregate element, if at least on child is invalid, this method will return false
     *
     * Note: A non-submit()'ed element will return false
     *
     * @return bool
     *
     * @see ElementInterface::error() To get error
     */
    public function valid(): bool;

    /**
     * Get the errors related to the element
     *
     * If the element is valid (i.e. has no errors), a null object is returned
     * The return value may contains the errors of the element or of its children
     *
     * In case of an aggregate element, if at least a child as an error, no "global" error should be present
     *
     * <code>
     * if (!$form->valid()) {
     *     return new JsonResponse($form->error()->print(new ApiFormErrorPrinter()), 400);
     * }
     * </code>
     *
     * @return FormError
     */
    public function error(): FormError;

    /**
     * Get the element's container
     *
     * The root element has no container, and return null
     *
     * The container is the element's adapter to ChildInterface, keeping a circular reference
     * `$element->container()->element() === $element` is always true
     *
     * @return ChildInterface|null The child, or null if the current element is the root element
     *
     * @see ElementInterface::setContainer() For define the container
     * @see ChildInterface::element() For get the element from the container
     */
    public function container(): ?ChildInterface;

    /**
     * Define the element's container
     * A new instance is returned without modifying the current one
     *
     * @param ChildInterface $container
     *
     * @return static The new element's instance, linked with the container
     *
     * @see ElementInterface::container() For get the container
     */
    public function setContainer(ChildInterface $container): self;

    /**
     * Get the root element of the form
     *
     * This value is never null : if the element has no parent, it must create a new root, and define it-self as the root element
     * If a container is defined, will return the root of the container's parent (i.e. `$element->container()->parent()->root()`)
     *
     * @return RootElementInterface
     */
    public function root(): RootElementInterface;

    /**
     * Get the view object of the element
     * Note: This method will only create the view for the element, independently of the parent.
     *       To generate the view within the parent, you should pass by ChildInterface::view()
     *
     * <code>
     * // On controller
     * $view = $form->view();
     *
     * // On view
     * echo $view['myElement']->class('form-control')->id('my-input');
     * </code>
     *
     * @param HttpFieldPath|null $field The used HTTP field name
     *
     * @return ElementViewInterface
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface;
}
