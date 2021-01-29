<?php

namespace Bdf\Form\PropertyAccess;

/**
 * Extract a property value and import it into the form element
 *
 * <code>
 * // Use the child name as property name
 * $builder->extractor(new Getter());
 *
 * // The property name is "myProp"
 * $builder->extractor(new Getter('myProp'));
 *
 * // Apply a transformation to the value
 * $builder->extractor(new Getter(function ($value, ChildInterface $input) {
 *    return doTransform($value);
 * }));
 *
 * // Define property name and transformer
 * $builder->extractor(new Getter('myProp', function ($value, ChildInterface $input) {
 *    return doTransform($value);
 * }));
 *
 * // Use a custom accessor. $mode is equals to ExtractorInterface::EXTRACTION
 * $builder->extractor(new Getter(null, null, function ($entity, $_, $mode, Getter $getter) {
 *    return $entity->myCustomGetter();
 * }));
 * </code>
 *
 * @see ChildBuilder::getter()
 */
final class Getter extends AbstractAccessor implements ExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function extract($source)
    {
        if ($this->customAccessor !== null) {
            $value = ($this->customAccessor)($source, null, self::EXTRACTION, $this);
        } else {
            $value = $this->propertyAccessor->getValue($source, $this->prepareAccessorPath($source));
        }

        if ($this->transformer) {
            $value = ($this->transformer)($value, $this->input);
        }

        return $value;
    }
}
