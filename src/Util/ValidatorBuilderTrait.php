<?php

namespace Bdf\Form\Util;

use Bdf\Form\Constraint\Closure;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

use function array_unshift;
use function is_callable;

/**
 * Trait for implements build of constraint validator
 *
 * @psalm-require-implements \Bdf\Form\ElementBuilderInterface
 */
trait ValidatorBuilderTrait
{
    /**
     * @var array<Constraint>
     */
    private array $constraints = [];
    private ?TransformerExceptionConstraint $transformerExceptionConstraint = null;

    /**
     * @var array<callable(RegistryInterface):(Constraint[])>
     */
    private array $constraintsProviders = [];

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
     * @param string|Constraint|null $message The error message. A custom constraint can be passed.
     * @param bool|null $allowNull Allow null value (default false).
     * @param callable|null $normalizer A normalizer callback to apply on the value before validation.
     *
     * @return $this
     *
     * @see NotBlank The used constraint
     */
    public function required(string|Constraint|null $message = null, ?bool $allowNull = null, ?callable $normalizer = null): static
    {
        if (!$message instanceof Constraint) {
            $message = new NotBlank(message: $message, allowNull: $allowNull, normalizer: $normalizer);
        }

        return $this->satisfy($message);
    }

    /**
     * {@inheritdoc}
     *
     * @see ElementBuilderInterface::satisfy()
     */
    final public function satisfy(Constraint|callable $constraint, ?string $message = null, bool $append = true): static
    {
        if (is_callable($constraint)) {
            $constraint = new Closure($constraint, $message);
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
    final public function ignoreTransformerException(bool $flag = true): static
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
    final public function transformerErrorMessage(string $message): static
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
    final public function transformerErrorCode(string $code): static
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
    final public function transformerExceptionValidation(callable $validationCallback): static
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
     * @param callable(RegistryInterface):(Constraint[]) $constraintsProvider
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
    private function getTransformerExceptionConstraint(): TransformerExceptionConstraint
    {
        if ($this->transformerExceptionConstraint) {
            return $this->transformerExceptionConstraint;
        }

        return $this->transformerExceptionConstraint = $this->defaultTransformerExceptionConstraint();
    }

    /**
     * Define the default TransformerExceptionConstraint
     * This method should be overridden to define options
     */
    protected function defaultTransformerExceptionConstraint(): TransformerExceptionConstraint
    {
        return new TransformerExceptionConstraint();
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
            $constraints = [...$constraints, ...$provider($registry)];
        }

        $constraints = [...$constraints, ...$this->constraints];

        return new ConstraintValueValidator($constraints, $this->getTransformerExceptionConstraint());
    }
}
