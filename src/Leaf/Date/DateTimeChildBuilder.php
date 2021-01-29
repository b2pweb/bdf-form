<?php

namespace Bdf\Form\Leaf\Date;

use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Leaf\Date\Transformer\DateTimeToTimestampTransformer;

/**
 * Child builder for date time elements
 *
 * @extends ChildBuilder<DateTimeElementBuilder>
 */
class DateTimeChildBuilder extends ChildBuilder
{
    /**
     * The model value of the input will be transformer to a timestamp
     *
     * <code>
     * // The entity : date is a timestamp
     * class MyEntity {
     *     public int $date;
     * }
     *
     * // Build the element
     * $builder->dateTime('date')->saveAsTimestamp()->getter()->setter();
     *
     * $form->import(MyEntity::get($id));
     * $form['date']->element()->value(); // Value is an instance of DateTime
     *
     * $entity = $form->value();
     * $entity->date; // date is a timestamp (i.e. integer value)
     * </code>
     *
     * @return $this
     *
     * @see DateTimeToTimestampTransformer
     */
    public function saveAsTimestamp(): self
    {
        return $this->modelTransformer(new DateTimeToTimestampTransformer());
    }
}
