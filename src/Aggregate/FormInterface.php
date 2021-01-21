<?php

namespace Bdf\Form\Aggregate;

/**
 * The base form element type
 *
 * @method \Bdf\Form\Aggregate\View\FormView view(?\Bdf\Form\Child\Http\HttpFieldPath $fieldPath = null)
 *
 * @template T
 * @extends ChildAggregateInterface<T>
 */
interface FormInterface extends ChildAggregateInterface
{
    /**
     * Attach the entity to generates when calling $form->value()
     * Works like import(), but without extracting properties values into children
     *
     * <code>
     * // Attach the entity to the form and submit values
     * $form
     *     ->attach($this->repository->get($request->get('id')))
     *     ->submit($request->post())
     * ;
     *
     * if (!$form->valid()) {
     *     throw new MyFormError();
     * }
     *
     * // Save the attached and updated entity
     * $this->repository->save($form->value());
     * </code>
     *
     * @param T|class-string<T>|callable():T $entity The entity object, or class name
     *
     * @return $this
     *
     * @see Form::import() For attach and extract values from properties
     */
    public function attach($entity): FormInterface;
}
