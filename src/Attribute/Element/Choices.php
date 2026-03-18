<?php

namespace Bdf\Form\Attribute\Element;

use Attribute;
use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Choice\ArrayChoice;
use Bdf\Form\Choice\Choiceable;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Choice\LazyChoice;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Leaf\StringElementBuilder;
use Nette\PhpGenerator\Literal;

/**
 * Define available values choice for the element
 *
 * The choices can be :
 * - a simple array of values (without labels)
 * - an associative array for provide a label (in key), and inner value (in value)
 * - a method name for resolving choices in lazy way
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->choices(['bar', 'baz']);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Choices(['bar', 'rab'])]
 *     private StringElement $foo;
 *
 *     #[Choices(['My label' => 'v1', 'Other label' => 'v2'])]
 *     private StringElement $bar;
 *
 *     #[Choices('loadBazValues', 'Invalid value')]
 *     private StringElement $baz;
 *
 *     // For dynamic choices, or with complex logic
 *     public function loadBazValues(): array
 *     {
 *         $values = [];
 *
 *         foreach (BazEntity::all() as $baz) {
 *             $values[$baz->label()] = $baz->id();
 *         }
 *
 *         return $values;
 *     }
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChoiceBuilderTrait::choices() The called method
 * @see Choiceable Supported element type
 * @see ArrayChoice Used when an array is given as parameter
 * @see LazyChoice Used when a method name is given as parameter
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Choices implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * Choice provider
         *
         * Can be a method name for load choices. The method must be public and declared on the form class,
         * with the prototype `public function (): array`
         *
         * If the value is an array, the key will be used as label (displayed value), and the value as real value
         * The label is not required.
         *
         * @var literal-string|array
         * @readonly
         */
        private string|array $choices,
        /**
         * The error message
         * If not provided, a default message will be used
         *
         * @var string|null
         * @readonly
         */
        private ?string $message = null,
        /**
         * Extra constraint options
         *
         * @var array
         * @readonly
         */
        private array $options = [],
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $options = $this->options;

        if ($this->message !== null) {
            $options['message'] = $options['multipleMessage'] = $this->message;
        }

        // Q&D fix for psalm because it does not recognize trait as type
        /** @var StringElementBuilder $builder */
        $builder->choices(
            is_string($this->choices) ? new LazyChoice([$form, $this->choices]) : $this->choices,
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $options = $this->options;

        if ($this->message !== null) {
            $options['message'] = $options['multipleMessage'] = $this->message;
        }

        if (is_string($this->choices)) {
            $generator->use(LazyChoice::class);
            $choices = new Literal('new LazyChoice([$form, ?])', [$this->choices]);
        } else {
            $choices = $this->choices;
        }

        $generator->line('$?->choices(?, ?);', [$name, $choices, $options]);
    }
}
