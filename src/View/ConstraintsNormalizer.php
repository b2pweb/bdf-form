<?php

namespace Bdf\Form\View;

use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use Bdf\Validator\Constraints\Chain;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Normalize symfony constraints to array
 * This normalization process permit to filter unserializable values, and disociate validation business and view rendering
 */
final class ConstraintsNormalizer
{
    static private $constraints = [
        NotBlank::class => [],
        Length::class => ['min' => null, 'max' => null],
        Count::class => ['min' => null, 'max' => null],
    ];

    /**
     * Process normalization
     * The return value consists of and array with constraint class as key, and associative array of constraints attributes as value
     *
     * @param ValueValidatorInterface $validator
     *
     * @return array
     * @see FieldViewInterface::constraints()
     */
    public static function normalize(ValueValidatorInterface $validator): array
    {
        if (!$validator instanceof ConstraintValueValidator) {
            return [];
        }

        // @todo do not use reflection
        $r = new \ReflectionProperty(ConstraintValueValidator::class, 'constraint');
        $r->setAccessible(true);
        $constraint = $r->getValue($validator);

        if ($constraint instanceof Chain) {
            $constraints = $constraint->constraints;
        } else {
            $constraints = [$constraint];
        }

        $normalizedConstraints = [];

        /** @var Constraint $constraint */
        foreach ($constraints as $constraint) {
            $className = get_class($constraint);

            if (isset(self::$constraints[$className])) {
                $normalizedConstraints[$className] = array_intersect_key((array) $constraint, self::$constraints[$className]);
            } elseif ($constraint->getDefaultOption() !== null) {
                $value = $constraint->{$constraint->getDefaultOption()};

                if (is_scalar($value)) {
                    $normalizedConstraints[$className] = [$constraint->getDefaultOption() => $value];
                }
            }
        }

        return $normalizedConstraints;
    }
}
