<?php

namespace Bdf\Form\Leaf;

use BackedEnum;
use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use InvalidArgumentException;
use LogicException;
use Override;
use UnitEnum;

use function assert;
use function is_subclass_of;
use function sprintf;

/**
 * Builder for an enum element
 *
 * <code>
 * $builder->enum('type', EnumType::class)
 *     ->backed(false) // Force the use of case name instead of its value
 * ;
 * </code>
 *
 * @see UnitEnumElement
 * @see BackedEnumElement
 * @see FormBuilderInterface::enum()
 *
 * @extends AbstractElementBuilder<UnitEnumElement|BackedEnumElement>
 */
final class EnumElementBuilder extends AbstractElementBuilder
{
    use ChoiceBuilderTrait;

    /**
     * @var class-string<UnitEnum>|null
     */
    private ?string $enumClass = null;

    /**
     * Use the backed value of the enum to resolve it:
     * - If true, the enum will be resolved using the backed value (BackedEnum)
     * - If false, the case name will be used
     * - If null, the behavior will depend on the enum type (backed or not)
     */
    private ?bool $backedEnum = null;

    public function __construct(?RegistryInterface $registry = null)
    {
        parent::__construct($registry);
    }

    /**
     * Define the enum class
     * This method should not be called manually, and will fail if an enum class is already defined.
     *
     * @param class-string<UnitEnum> $enumClass
     * @return $this
     *
     * @internal
     */
    public function enumClass(string $enumClass): static
    {
        if ($this->enumClass !== null) {
            throw new LogicException(sprintf('Enum class "%s" is already defined.', $enumClass));
        }

        $this->enumClass = $enumClass;
        return $this;
    }

    /**
     * Use the backed value of the enum to resolve it:
     * - If true, the enum will be resolved using the backed value (BackedEnum)
     * - If false, the case name will be used
     * - If null, the behavior will depend on the enum type (backed or not)
     *
     * @param bool|null $flag
     * @return $this
     */
    public function backed(?bool $flag = true): static
    {
        if ($this->enumClass !== null && $flag && !is_subclass_of($this->enumClass, BackedEnum::class)) {
            throw new InvalidArgumentException(sprintf('The enum class "%s" is not a backed enum', $this->enumClass));
        }

        $this->backedEnum = $flag;

        return $this;
    }

    #[Override]
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): UnitEnumElement|BackedEnumElement
    {
        $enumClass = $this->enumClass;
        $isBacked = $this->backedEnum;

        if ($enumClass === null) {
            throw new LogicException('An enum class must be provided');
        }

        $isBacked ??= is_subclass_of($enumClass, BackedEnum::class);
        assert(!$isBacked || is_subclass_of($enumClass, BackedEnum::class));

        return $isBacked
            ? new BackedEnumElement($enumClass, $validator, $transformer, $this->choices)
            : new UnitEnumElement($enumClass, $validator, $transformer, $this->choices)
        ;
    }
}
