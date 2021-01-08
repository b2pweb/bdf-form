<?php

namespace Bdf\Form\Choice;

/**
 * View object for choice
 *
 * <code>
 * <?php foreach ($element->choices() as $choice): ?>
 *     <label>
 *         <?php echo $choice->label(); ?>
 *         <input type="radio" name="<?php echo $element->name(); ?>" value="<?php echo $choice->value()" <?php echo $choice->selected() ? 'selected' : ''; ?> />
 *     </label>
 * <?php endforeach; ?>
 * </code>
 */
class ChoiceView
{
    /**
     * The label displayed to humans.
     *
     * @var string
     */
    private $label;

    /**
     * The view representation of the choice.
     *
     * @var mixed
     */
    private $value;

    /**
     * The view representation of the choice.
     *
     * @var boolean
     */
    private $selected;

    /**
     * Creates a new choice view.
     *
     * @param mixed $value The option value
     * @param string|int $label The label displayed to humans
     * @param boolean $selected This choice is selected
     */
    public function __construct($value, $label, bool $selected = false)
    {
        $this->value = $value;
        $this->selected = $selected;

        // If there is no label, we take the value as label
        $this->label = !is_string($label) ? $value : $label;
    }

    /**
     * Get the display label
     * If the label is not declared, return the value
     *
     * @return string
     */
    public function label(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * Get the option value
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * Does the current option is selected ?
     *
     * @return bool
     */
    public function selected(): bool
    {
        return $this->selected;
    }

    /**
     * @param bool $selected
     */
    public function setSelected(bool $selected): void
    {
        $this->selected = $selected;
    }
}
