<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Trait for implements build of constraint validator
 */
trait ValidatorBuilderTrait
{
    /**
     * @var Constraint[]
     */
    private $constraints = [];

    /**
     * @see ElementBuilderInterface::satisfy()
     */
    final public function satisfy($constraint, $options = null, $append = true)
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
        return ConstraintValueValidator::fromConstraints(array_map([$this->registry(), 'constraint'], $this->constraints));
    }
}
