<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Trait for implements build of constraint validator
 */
trait ValidatorBuilderTrait
{
    /**
     * @var array<Constraint|string|array>
     */
    private $constraints = [];

    /**
     * @var callable[]
     */
    private $constraintsProviders = [];

    /**
     * Mark this input as required
     * Calling this method is equivalent as calling `satisfy(new NotBlank($options))`
     *
     * Note: The constraint is not prepend, but simply added at the end of constraints.
     *       To stop validation process if the value is empty, this method must be called before all other `satisfy()`.
     *
     * Usage:
     * <code>
     * $builder->required(); // Mark as required, using default message
     * $builder->required('This field is required'); // With custom message
     * $builder->required(['allowNull' => true]); // With custom options
     * </code>
     *
     * @param array|string|null $options The constraint option. Is a string is given, it will be used as error message
     *
     * @return $this
     *
     * @see NotBlank The used constraint
     */
    final public function required($options = null)
    {
        if (!$options instanceof Constraint) {
            if (is_string($options)) {
                $options = ['message' => $options];
            }

            $options = new NotBlank($options);
        }

        return $this->satisfy($options);
    }

    /**
     * {@inheritdoc}
     *
     * @see ElementBuilderInterface::satisfy()
     */
    final public function satisfy($constraint, $options = null, bool $append = true)
    {
        if ($options !== null) {
            $constraint = [$constraint, $options];
        }

        if ($append === true) {
            $this->constraints[] = $constraint;
        } else {
            array_unshift($this->constraints, $constraint);
        }

        return $this;
    }

    /**
     * Register a new constraints provider
     * Constrains providers are call when building validator
     * It should return an array of constraints
     *
     * <code>
     * $builder->addConstraintsProvider(function() {
     *     return [
     *         new FooConstraint($this->fooValue),
     *         new BarConstraint($this->barValue),
     *     ];
     * });
     * </code>
     *
     * @param callable():Constraint[] $constraintsProvider
     */
    final protected function addConstraintsProvider(callable $constraintsProvider): void
    {
        $this->constraintsProviders[] = $constraintsProvider;
    }

    /**
     * Get the registry instance
     *
     * @return RegistryInterface
     */
    abstract protected function registry(): RegistryInterface;

    /**
     * Create the value validator for the element
     *
     * @return ValueValidatorInterface
     */
    private function buildValidator(): ValueValidatorInterface
    {
        $constraints = [];

        foreach ($this->constraintsProviders as $provider) {
            $constraints = array_merge($constraints, $provider());
        }

        $constraints = array_merge($constraints, array_map([$this->registry(), 'constraint'], $this->constraints));

        return ConstraintValueValidator::fromConstraints($constraints);
    }
}
