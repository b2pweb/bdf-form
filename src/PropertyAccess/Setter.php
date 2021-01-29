<?php

namespace Bdf\Form\PropertyAccess;

/**
 * Set the property value using the element value
 *
 * <code>
 * // Use the child name as property name
 * $builder->hydrator(new Setter());
 *
 * // The property name is "myProp"
 * $builder->hydrator(new Setter('myProp'));
 *
 * // Apply a transformation to the value
 * $builder->hydrator(new Setter(function ($value, ChildInterface $input) {
 *    return doTransform($value);
 * }));
 *
 * // Define property name and transformer
 * $builder->hydrator(new Setter('myProp', function ($value, ChildInterface $input) {
 *    return doTransform($value);
 * }));
 *
 * // Use a custom accessor. $mode is equals to HydratorInterface::HYDRATION
 * $builder->hydrator(new Setter(null, null, function ($entity, $value, $mode, Setter $setter) {
 *    return $entity->myCustomSetter($value);
 * }));
 * </code>
 *
 * @see ChildBuilder::setter()
 */
final class Setter extends AbstractAccessor implements HydratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(&$target, $value): void
    {
        if ($this->transformer) {
            $value = ($this->transformer)($value, $this->input);
        }

        if ($this->customAccessor !== null) {
            ($this->customAccessor)($target, $value, self::HYDRATION, $this);
        } else {
            $this->propertyAccessor->setValue($target, $this->prepareAccessorPath($target), $value);
        }
    }
}
