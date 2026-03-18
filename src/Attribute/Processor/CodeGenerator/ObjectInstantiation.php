<?php

namespace Bdf\Form\Attribute\Processor\CodeGenerator;

use Nette\PhpGenerator\Literal;
use ReflectionObject;

use function get_class;

/**
 * Utility class for generate an object instantiation
 */
final class ObjectInstantiation
{
    public function __construct(
        /**
         * The class name to instantiate
         *
         * @var class-string
         * @readonly
         */
        private string $className,
        /**
         * List of constructor parameters
         *
         * @var array
         * @readonly
         */
        private array $constructorParameters = [],
    ) {
    }

    /**
     * Render the object instantiation
     *
     * @param ClassGenerator|null $generator The generator to simplify the class name. If null, the class name will be used as is
     *
     * @return Literal
     */
    public function render(?ClassGenerator $generator = null): Literal
    {
        $className = $generator ? $generator->useAndSimplifyType($this->className) : $this->className;

        return Literal::new($className, $this->constructorParameters);
    }

    /**
     * Configure the ObjectInstantiation utility to generate
     * the constructor call with a single array parameter,
     * use to inject all public properties of an object.
     *
     * This method should be used for symfony constraints.
     * Properties with default value will be ignored.
     *
     * @param object $object
     * @return self
     */
    public static function singleArrayParameter(object $object): self
    {
        return new self(
            get_class($object),
            [self::extractPublicProperties($object)]
        );
    }

    /**
     * Extract all public properties of the object, ignoring default values
     *
     * @param object $object
     *
     * @return array<string, mixed>
     * @psalm-suppress MixedAssignment
     */
    private static function extractPublicProperties(object $object): array
    {
        $reflectionObject = new ReflectionObject($object);
        $properties = [];

        foreach ($reflectionObject->getProperties() as $property) {
            if ($property->isPublic() && !$property->isStatic()) {
                $property->setAccessible(true);
                $value = $property->getValue($object);

                if ($property->getDefaultValue() !== $value) {
                    $properties[$property->getName()] = $value;
                }
            }
        }

        return $properties;
    }
}
