<?php

namespace Bdf\Form\View;

use Bdf\Form\Choice\Choiceable;
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Choice\ChoiceView;

/**
 * Base type for HTTP input / field
 * The implementations must be renderable
 */
interface FieldViewInterface extends ElementViewInterface, Renderable
{
    /**
     * The HTTP field name
     *
     * @return string
     */
    public function name(): string;

    /**
     * The HTTP value
     *
     * @return mixed
     */
    public function value();

    /**
     * Does the current field is required (i.e. the value must not be empty)
     *
     * @return bool
     */
    public function required(): bool;

    /**
     * An array of constraints
     * The return value consists of a map with the constraint class name as key, and attributes as value
     * Like:
     * [
     *     Length::class => ['min' => 5, 'max' => 30],
     *     NotBlank::class => [],
     * ]
     *
     * @return array
     *
     * @see ConstraintsNormalizer::normalize() For normalize symfony constraints
     */
    public function constraints(): array;

    /**
     * Get configure choices on the element
     * Returns null if no choices has been configured
     *
     * <code>
     *   <select name="<?php echo $view->name(); ?>">
     *      <?php foreach ($view->choices() as $choice): ?>
     *          <option value="<?php echo $choice->value(); ?>"<?php echo $choice->selected() ? ' selected' : ''; ?>><?php echo $choice->label(); ?></option>
     *      <?php endforeach; ?>
     *   </select>
     * </code>
     *
     * @return ChoiceView[]|null
     *
     * @see Choiceable
     * @see ChoiceInterface::view()
     */
    public function choices(): ?array;

    /**
     * Render the field view
     *
     * Usage:
     * <code>
     * echo $view->render(); // Use the default renderer
     * echo $view->render(new CustomRenderer()); // Use a custom renderer
     * </code>
     *
     * @param FieldViewRendererInterface|null $renderer The renderer to use. If null, will use the default renderer (i.e. html renderer)
     *
     * @return string
     */
    public function render(?FieldViewRendererInterface $renderer = null): string;
}
