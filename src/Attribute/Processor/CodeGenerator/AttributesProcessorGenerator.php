<?php

namespace Bdf\Form\Attribute\Processor\CodeGenerator;

use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use InvalidArgumentException;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;

/**
 * Utility class for generate the AttributesProcessor class
 */
final class AttributesProcessorGenerator extends ClassGenerator
{
    private Method $method;

    /**
     * @param non-empty-string $className The class name to generate. Must have a namespace
     * @throws InvalidArgumentException if a namespace is not provided, or if the class name is not valid
     */
    public function __construct(string $className)
    {
        if (($classNamePos = strrpos($className, '\\')) === false) {
            throw new InvalidArgumentException('The class name must have a namespace');
        }

        parent::__construct(
            $namespace = new PhpNamespace(substr($className, 0, $classNamePos)),
            $namespace->addClass(substr($className, $classNamePos + 1))
        );

        $this->implements(AttributesProcessorInterface::class);

        $this->method = $this->implementsMethod(AttributesProcessorInterface::class, 'configureBuilder');
    }

    /**
     * Add a line on the body method of "configureBuilder"
     *
     * @param string $line Line to add. Set empty string (default parameter) to simply add empty new line
     * @param array $args Placeholder arguments
     *
     * @return self
     */
    public function line(string $line = '', array $args = []): self
    {
        $this->method->addBody($line, $args);

        return $this;
    }

    /**
     * Create a new expression, with use class name
     *
     * @param class-string $className The class to create
     * @param array $parameters The constructor parameters
     * @param string|null $classAlias Class alias to use
     *
     * @return Literal
     */
    public function new(string $className, array $parameters, ?string $classAlias = null): Literal
    {
        $className = $this->useAndSimplifyType($className, $classAlias);

        return new Literal('new ?(...?:)', [new Literal($className), $parameters]);
    }

    /**
     * Print the class code
     *
     * @return string
     */
    public function print(): string
    {
        return $this->printer()->printNamespace($this->namespace());
    }
}
