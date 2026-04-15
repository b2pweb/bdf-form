<?php

namespace Bdf\Form\Struct;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Attribute\Aggregate\Optional;
use Bdf\Form\Attribute\BuilderMethodCall;
use Bdf\Form\Attribute\Child\DefaultValue;
use Bdf\Form\Attribute\Child\GetSet;
use Bdf\Form\Attribute\Element\Date\DateTimeClass;
use Bdf\Form\Attribute\Element\Required;
use Bdf\Form\Attribute\Element\StructClass;
use Bdf\Form\Attribute\Form\CallbackGenerator;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\CompileAttributesProcessor;
use Bdf\Form\Attribute\Processor\ConfigureFormBuilderStrategy;
use Bdf\Form\Attribute\Processor\ElementPropertyMetadata;
use Bdf\Form\Attribute\Processor\ProcessorMetadata;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Attribute\Processor\ReflectionStrategyInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\UnitEnumElement;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Closure;
use DateTime;
use DateTimeInterface;
use libphonenumber\PhoneNumber;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use UnitEnum;

use function assert;
use function class_exists;
use function in_array;
use function is_string;
use function is_subclass_of;

/**
 * Factory of {@see AttributesProcessorInterface} in context of struct form configuration
 */
final class StructAttributesProcessorFactory
{
    /**
     * @var array<string, class-string<ElementInterface>>
     */
    private array $mapping = [
        'int' => IntegerElement::class,
        'string' => StringElement::class,
        'array' => ArrayElement::class,
        'float' => FloatElement::class,
        'boot' => BooleanElement::class,
        DateTime::class => DateTimeElement::class,
        DateTimeInterface::class => DateTimeElement::class,
        PhoneNumber::class => PhoneElement::class,
    ];

    /**
     * Map a property type to an element type, using {@see is_subclass_of()} to check for inheritance
     *
     * @var array<class-string, class-string<ElementInterface>>
     */
    private array $mappingInstanceOf = [
        DateTimeInterface::class => DateTimeElement::class,
        UnitEnum::class => UnitEnumElement::class,
    ];

    /**
     * Associate a post processor for a given form element type.
     * The exact element type will be used to resolve the processor.
     *
     * @var array<class-string<ElementInterface>, Closure(ElementPropertyMetadata, class-string|object):void>
     */
    private array $elementPostProcessors = [];

    public function __construct()
    {
        $this->elementPostProcessors = [
            StructForm::class => $this->postProcessStructForm(...),
            DateTimeElement::class => $this->postProcessDateTimeElement(...),
            UnitEnumElement::class => $this->postProcessEnumElement(...),
        ];
    }

    /**
     * Create the attributes processor with on the fly form configuration
     * This processor will not use any cache
     */
    public function runtime(): AttributesProcessorInterface
    {
        return $this->create(new ConfigureFormBuilderStrategy());
    }

    /**
     * Create the attributes processor with the given strategy
     */
    public function create(ReflectionStrategyInterface $strategy): AttributesProcessorInterface
    {
        return new ReflectionProcessor(
            $strategy,
            $this->mapElementType(...),
            $this->postProcess(...),
        );
    }

    /**
     * Create the attributes processor with code generation for form configuration
     *
     * @param Closure(class-string):non-empty-string $classNameResolver
     * @param Closure(class-string<AttributesProcessorInterface>):non-empty-string $fileNameResolver
     */
    public function generated(Closure $classNameResolver, Closure $fileNameResolver): AttributesProcessorInterface
    {
        return new CompileAttributesProcessor(
            $classNameResolver,
            $fileNameResolver,
            $this->create(...),
        );
    }

    public function mapElementType(string $type): string
    {
        $mappedType = $this->mapping[$type] ?? null;

        if ($mappedType !== null) {
            return $mappedType;
        }

        foreach ($this->mappingInstanceOf as $instanceOf => $mappedType) {
            if (is_subclass_of($type, $instanceOf)) {
                return $mappedType;
            }
        }

        if (class_exists($type)) {
            return StructForm::class;
        }

        return $type;
    }

    /**
     * @param ProcessorMetadata $metadata
     * @param object|class-string $context
     */
    public function postProcess(ProcessorMetadata $metadata, object|string $context): void
    {
        if (
            is_string($context)
            && !$metadata->hasFormAttribute(Generates::class)
            && !$metadata->hasFormAttribute(CallbackGenerator::class)
        ) {
            $metadata->addFormAttribute(new Generates($context));
        }

        foreach ($metadata->elementProperties() as $property) {
            $hasGetSet = $property->hasAttribute(GetSet::class);
            $hasGetter = $hasGetSet || $property->hasAttribute(ExtractorInterface::class);
            $hasSetter = $hasGetSet || $property->hasAttribute(HydratorInterface::class);

            if (!$hasGetter && !$hasSetter) {
                $property->addAttribute(new GetSet());
            }

            if (
                !$property->hasAttribute(Required::class)
                && $this->isRequired($property->property)
            ) {
                /** @psalm-suppress InvalidArgument */
                $property->addAttribute(new Required());
            }

            if (!$property->hasAttribute(DefaultValue::class)) {
                $defaultValue = $this->getDefaultValue($property->property);

                if ($defaultValue !== null) {
                    $property->addAttribute(new DefaultValue($defaultValue));
                }
            }

            $postProcessor = $this->elementPostProcessors[$property->elementType] ?? null;

            if ($postProcessor !== null) {
                $postProcessor($property, $context);
            }
        }
    }

    private function isRequired(ReflectionProperty $property): bool
    {
        if ($property->hasDefaultValue()) {
            return false;
        }

        $type = $property->getType();

        // Types that allows empty values are not considered as required
        if ($type === null || $type->allowsNull() || (string) $type === 'array') {
            return false;
        }

        return !$type->allowsNull();
    }

    private function getDefaultValue(ReflectionProperty $property): mixed
    {
        if ($property->hasDefaultValue()) {
            return $property->getDefaultValue();
        }

        if (!$property->isPromoted()) {
            return null;
        }

        $constructor = $property->getDeclaringClass()->getConstructor();
        assert($constructor !== null);

        $parameter = array_find($constructor->getParameters(), static fn (ReflectionParameter $parameter) => $parameter->getName() === $property->getName());
        assert($parameter !== null);

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }

    /**
     * @param ElementPropertyMetadata $property
     * @param class-string|object $context
     */
    private function postProcessStructForm(ElementPropertyMetadata $property, string|object $context): void
    {
        if (!$property->hasAttribute(StructClass::class)) {
            $type = $property->property->getType();
            assert($type instanceof ReflectionNamedType);

            $typeName = $type->getName();
            assert(class_exists($typeName));

            $property->addAttribute(new StructClass($typeName));
        }

        if (!$property->hasAttribute(Required::class) && !$property->hasAttribute(Optional::class)) {
            $property->addAttribute(new Optional());
        }
    }

    /**
     * @param ElementPropertyMetadata $property
     * @param class-string|object $context
     */
    private function postProcessDateTimeElement(ElementPropertyMetadata $property, string|object $context): void
    {
        if (!$property->hasAttribute(DateTimeClass::class)) {
            $type = $property->property->getType();
            assert($type instanceof ReflectionNamedType);

            $typeName = $type->getName();

            if (!in_array($typeName, [DateTime::class, DateTimeInterface::class], true)) {
                assert(is_subclass_of($typeName, DateTimeInterface::class));

                /** @psalm-suppress InvalidArgument */
                $property->addAttribute(new DateTimeClass($typeName));
            }
        }
    }

    /**
     * @param ElementPropertyMetadata $property
     * @param class-string|object $context
     */
    private function postProcessEnumElement(ElementPropertyMetadata $property, string|object $context): void
    {
        $type = $property->property->getType();
        assert($type instanceof ReflectionNamedType);

        $property->addAttribute(new BuilderMethodCall('enumClass', [$type->getName()]));
    }
}
