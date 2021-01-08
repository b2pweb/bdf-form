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
 */
interface FieldSetViewInterface extends ElementViewInterface, ArrayAccess, Traversable
{

}
