<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Trait for implements build of constraint validator
 *
 * @psalm-require-implements \Bdf\Form\ElementBuilderInterface
 */
trait ValidatorBuilderTrait
{
    /**
     * @var array<Constraint|string|array>
     */
    private $constraints = [];

    /**
     * @var TransformerExceptionConstraint|null
     */
    private $transformerExceptionConstraint;

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
     * Ignore the transformer exception
     * If true, when a transformation fails, the error will be ignored, and the standard validation process will be performed
     *
     * @param bool $flag true to ignore
     *
     * @return $this
     *
     * @see TransformerExceptionConstraint::$ignoreException
     */
    final public function ignoreTransformerException(bool $flag = true)
    {
        $this->getTransformerExceptionConstraint()->ignoreException = $flag;

        return $this;
    }

    /**
     * Define the error message to show when the transformer raise an exception
     *
     * @param string $message The error message
     * @return $this
     *
     * @see TransformerExceptionConstraint::$message
     */
    final public function transformerErrorMessage(string $message)
    {
        $this->getTransformerExceptionConstraint()->message = $message;

        return $this;
    }

    /**
     * Define the error code of the transformer error
     *
     * @param string $code The error code
     * @return $this
     *
     * @see TransformerExceptionConstraint::$code
     */
    final public function transformerErrorCode(string $code)
    {
        $this->getTransformerExceptionConstraint()->code = $code;

        return $this;
    }

    /**
     * Define custom transformer exception validation callback
     * Allow to define an error message and code corresponding to the exception, or ignore the exception
     *
     * The callback takes as parameters :
     * 1. The raw HTTP value
     * 2. The transformer exception constraint, as in-out parameter for get the exception and set message and code
     * 3. The form element
     *
     * The return value should return false to ignore the error, or true to add the error
     *
     * <code>
     * $builder->integer('value')->transformerExceptionValidation(function ($value, TransformerExceptionConstraint $constraint, ElementInterface $element) {
     *     if ($constraint->exception instanceof MyException) {
     *         // Define the error message and code
     *         $constraint->message = 'My error';
     *         $constraint->code = 'MY_ERROR';
     *
     *         return true;
     *     }
     *
     *     // Ignore the exception
     *     return false;
     * });
     * </code>
     *
     * @param callable(mixed,\Bdf\Form\Validator\TransformerExceptionConstraint,\Bdf\Form\ElementInterface):bool $validationCallback
     * @return $this
     *
     * @see TransformerExceptionConstraint::$code
     */
    final public function transformerExceptionValidation(callable $validationCallback)
    {
        $this->getTransformerExceptionConstraint()->validationCallback = $validationCallback;

        return $this;
    }

    /**
     * Register a new constraints provider
     * Constrains providers are call when building validator
     * It should return an array of constraints
     *
     * <code>
     * $builder->addConstraintsProvider(function(RegistryInterface $registry) {
     *     return [
     *         new FooConstraint($this->fooValue),
     *         new BarConstraint($this->barValue),
     *     ];
     * });
     * </code>
     *
     * @param callable(RegistryInterface):Constraint[] $constraintsProvider
     */
    final protected function addConstraintsProvider(callable $constraintsProvider): void
    {
        $this->constraintsProviders[] = $constraintsProvider;
    }

    /**
     * Get or create the transformer exception constraint
     *
     * @return TransformerExceptionConstraint
     */
    final private function getTransformerExceptionConstraint(): TransformerExceptionConstraint
    {
        if ($this->transformerExceptionConstraint) {
            return $this->transformerExceptionConstraint;
        }

        return $this->transformerExceptionConstraint = new TransformerExceptionConstraint($this->defaultTransformerExceptionConstraintOptions());
    }

    /**
     * Define the default constraints options for the TransformerExceptionConstraint
     * This method should be overridden for define options
     *
     * @return array
     */
    protected function defaultTransformerExceptionConstraintOptions(): array
    {
        return [];
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
        $registry = $this->registry();
        $constraints = [];

        foreach ($this->constraintsProviders as $provider) {
            $constraints = array_merge($constraints, $provider($registry));
        }

        foreach ($this->constraints as $constraint) {
            $constraints[] = $registry->constraint($constraint);
        }

        return new ConstraintValueValidator($constraints, $this->getTransformerExceptionConstraint());
    }
}
