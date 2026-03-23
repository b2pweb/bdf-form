<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;
use Override;
use ReflectionClass;

use function class_exists;
use function is_callable;
use function is_object;
use function is_string;

/**
 * The base value generator implementation
 *
 * <code>
 * (new ValueGenerator())->generate($form); // Will generate an empty array
 * (new ValueGenerator(MyEntity::class))->generate($form); // Will call the default constructor of MyEntity
 * (new ValueGenerator($entity))->generate($form); // Will clone the instance of $entity
 * (new ValueGenerator(function (FormInterface $form) { return new MyEntity(...); }))->generate($form); // Custom generator
 * </code>
 *
 * @template T as array|object
 * @implements ValueGeneratorInterface<T>
 *
 * @psalm-suppress InvalidDocblock
 */
final class ValueGenerator implements ValueGeneratorInterface
{
    /**
     * @var ValueGeneratorInterface<T>
     */
    private ValueGeneratorInterface $generator;

    /**
     * ValueGenerator constructor.
     *
     * @param callable(ElementInterface):T|T|class-string<T> $value
     */
    public function __construct(mixed $value = [])
    {
        $this->generator = self::fromValue($value, true);
    }

    #[Override]
    public function attach(mixed $entity): void
    {
        $this->generator = self::fromValue($entity, false);
    }

    #[Override]
    public function generate(ElementInterface $element): array|object
    {
        return $this->generator->generate($element);
    }

    #[Override]
    public function finalize(object|array $value): object|array
    {
        return $this->generator->finalize($value);
    }

    /**
     * @param callable(ElementInterface):U|U|class-string<U> $value
     * @return ValueGeneratorInterface<U>
     * @template U as array|object
     */
    private static function fromValue(mixed $value, bool $cloneObjectValue): ValueGeneratorInterface
    {
        if (is_string($value) && class_exists($value)) {
            $calUseDefaultConstructor = (new ReflectionClass($value)->getConstructor()?->getNumberOfRequiredParameters() ?? 0) === 0;

            return $calUseDefaultConstructor
                ? new DefaultConstructorValueGenerator($value)
                : new ConstructorValueGenerator($value)
            ;
        }

        if (is_callable($value)) {
            return new ClosureValueGenerator($value(...));
        }

        if (is_object($value) && $cloneObjectValue) {
            return new ObjectValueGenerator($value);
        }

        return new SimpleValueGenerator($value);
    }
}
