<?php

namespace Bdf\Form\Aggregate;

/**
 * The base form element type
 */
interface FormInterface extends ChildAggregateInterface
{
    /**
     * Attach the entity to generates when calling $form->value()
     * Works like import(), but without extracting properties values into children
     *
     * @param object|string $entity The entity object, or class name
     *
     * @return $this
     *
     * @see Form::import() For attach and extract values from properties
     */
    public function attach($entity): FormInterface;
}
