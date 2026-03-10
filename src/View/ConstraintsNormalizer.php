<?php

namespace Bdf\Form\View;

use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\ValueValidatorInterface;
use ReflectionClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use function assert;
use function get_object_vars;

/**
 * Normalize symfony constraints to array
 * This normalization process permit to filter unserializable values, and disociate validation business and view rendering
 */
final class ConstraintsNormalizer
{
    /**
     * @var array<class-string<\Symfony\Component\Validator\Constraint>, array<string, mixed>>
     */
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

        $normalizedConstraints = [];

        foreach ($validator->constraints() as $constraint) {
            $className = get_class($constraint);

            if (isset(self::$constraints[$className])) {
                $normalizedConstraints[$className] = array_intersect_key(get_object_vars($constraint), self::$constraints[$className]);
            } elseif (($value = self::getDefaultOption($constraint)) !== null) {
                $normalizedConstraints[$className] = $value;
            }
        }

        return $normalizedConstraints;
    }

    /**
     * Extract the default option of a constraint
     * The default option is the first argument of the constraint constructor.
     *
     * This method will return null if the default option is not defined,
     * or if the value of the default option is not a scalar value.
     *
     * @return array<string, scalar>|null
     */
    private static function getDefaultOption(Constraint $constraint): ?array
    {
        $ctor = (new ReflectionClass($constraint))->getConstructor();
        assert($ctor !== null);

        $firstParam = $ctor->getParameters()[0] ?? null;
        $firstParamName = $firstParam?->getName();

        if ($firstParamName !== 'options' && $firstParamName !== 'message') {
            $option = $firstParamName;
        } else {
            return null;
        }

        if ($option === null) {
            return null;
        }

        // Cache the option name for the constraint class, to avoid reflection on next calls
        self::$constraints[get_class($constraint)][$option] = null;

        $value = $constraint->{$option} ?? null;

        if (!is_scalar($value)) {
            return null;
        }

        return [$option => $value];
    }
}
