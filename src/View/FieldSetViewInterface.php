<?php

namespace Bdf\Form\View;

use ArrayAccess;
use Traversable;

/**
 * View for aggregate element
 * This element is not renderable, but contains renderable elements
 * Use an array access to get child, and render is
 *
 * Usage:
 * <code>
 * <div>
 *     <label for="firstName">First name</label>
 *     <?php echo $view['person']['firstName']->id('firstName'); ?>
 * </div>
 * <div>
 *     <label for="lastName">Last name</label>
 *     <?php echo $view['person']['lastName']->id('lastName'); ?>
 * </div>
 * </code>
 *
 * @see \Bdf\Form\Aggregate\ChildAggregateInterface
 *
 * @template E
 *
 * @extends ArrayAccess<array-key, E>
 * @extends Traversable<array-key, E>
 *
 * @method array errors()
 */
interface FieldSetViewInterface extends ElementViewInterface, ArrayAccess, Traversable
{
    /**
     * Get errors of all children
     * If there are no errors, this method should return an empty array
     *
     * The returned array is indexed by the child name, and the value is the error message as string when child is a leaf element,
     * or an array when child is an aggregate element
     *
     * @return array<array-key, string|array>
     * @since 1.5
     * @todo uncomment in 2.0
     */
    //public function errors(): array;
}
