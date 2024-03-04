<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Aggregate\Form;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Button\View\ButtonViewInterface;
use Bdf\Form\View\ElementViewInterface;
use Bdf\Form\View\ElementViewTrait;
use Bdf\Form\View\FieldSetViewInterface;
use Bdf\Form\View\FieldSetViewTrait;
use Bdf\Form\View\FieldViewInterface;
use IteratorAggregate;

/**
 * View for a form element
 * Works for root and embedded forms
 *
 * <code>
 *  <div><?php echo $form['foo']; ?></div>
 *  <div><?php echo $form['bar']; ?></div>
 *  <fieldset>
 *      <div><?php echo $form['embedded']['a']; ?></div>
 *      <div><?php echo $form['embedded']['b']; ?></div>
 *  </fieldset>
 *  <?php echo $form->button('btn'); ?>
 *  <!-- Array access works also for buttons -->
 *  <?php echo $form['btn']; ?>
 * </code>
 *
 * @see Form::view()
 *
 * @implements IteratorAggregate<string, ElementViewInterface|ButtonViewInterface>
 * @implements FieldSetViewInterface<ElementViewInterface|ButtonViewInterface>
 */
final class FormView implements IteratorAggregate, FieldSetViewInterface
{
    use ElementViewTrait;
    use FieldSetViewTrait {
        FieldSetViewTrait::hasError insteadof ElementViewTrait;
    }

    /**
     * @var ButtonViewInterface[]
     */
    private $buttons = [];

    /**
     * FormView constructor.
     *
     * @param string $type
     * @param string|null $error
     * @param ElementViewInterface[] $elements
     */
    public function __construct(string $type, ?string $error, array $elements)
    {
        $this->type = $type;
        $this->error = $error;
        $this->elements = $elements;
    }

    /**
     * {@inheritdoc}
     *
     * @return ElementViewInterface|ButtonViewInterface
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->elements[$offset] ?? $this->buttons[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->elements[$offset]) || isset($this->buttons[$offset]);
    }

    /**
     * Get all available buttons
     *
     * <code>
     *  <div class="form-footer">
     *      <?php foreach ($view->buttons() as $button): ?>
     *          <?php echo $button->class('btn btn-default'); ?>
     *      <?php endforeach; ?>
     *  </div>
     * </code>
     *
     * @return ButtonViewInterface[]
     */
    public function buttons(): array
    {
        return $this->buttons;
    }

    /**
     * Get a button by its name
     *
     * <code>
     * echo $form->button('btn')->inner('Save')->class('btn btn-primary');
     * </code>
     *
     * @param string $name The button name
     *
     * @return ButtonViewInterface|null
     * @see ButtonViewInterface::name()
     */
    public function button(string $name): ?ButtonViewInterface
    {
        return $this->buttons[$name] ?? null;
    }

    /**
     * Get HTTP fields and values for all inputs
     *
     * @return array<string, mixed>
     * @see FormInterface::httpValue() This value should be same as calling this method
     */
    public function value(): array
    {
        return self::extractValue($this);
    }

    /**
     * Change the form type
     * Used internally by CustomForm
     *
     * @param string $type The form class name
     * @internal
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Set form buttons
     *
     * @param ButtonViewInterface[] $buttons
     * @internal
     */
    public function setButtons(array $buttons): void
    {
        $this->buttons = $buttons;
    }

    private static function extractValue(FieldSetViewInterface $aggregate): array
    {
        $values = [];

        foreach ($aggregate as $child) {
            if ($child instanceof FieldViewInterface) {
                $values[$child->name()] = $child->value();
            } elseif ($child instanceof FieldSetViewInterface) {
                $values += self::extractValue($child);
            }
        }

        return $values;
    }
}
