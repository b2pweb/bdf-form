<?php

namespace Bdf\Form\Attribute\Processor\CodeGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;
use Nette\Utils\Type;

/**
 * Utility class for generate a generic class
 */
class ClassGenerator
{
    private PhpNamespace $namespace;
    private ClassType $class;
    private Printer $printer;

    public function __construct(PhpNamespace $namespace, ClassType $class, ?Printer $printer = null)
    {
        $this->namespace = $namespace;
        $this->class = $class;
        $this->printer = $printer ?? new PsrPrinter();
    }

    /**
     * Get the namespace object
     *
     * @return PhpNamespace
     */
    final public function namespace(): PhpNamespace
    {
        return $this->namespace;
    }

    /**
     * Get the current class instance
     *
     * @return ClassType
     */
    final public function class(): ClassType
    {
        return $this->class;
    }

    /**
     * Add an implemented interface on the class, and add use statement
     *
     * @param class-string $interface
     *
     * @return self
     */
    final public function implements(string $interface): self
    {
        $this->class->addImplement($interface);
        $this->namespace->addUse($interface);

        return $this;
    }

    /**
     * Implements a method of an interface, and auto-use all declared types (parameters and return type)
     *
     * @param class-string $interface The interface where the method is declared
     * @param literal-string $methodName The method name to implements
     *
     * @return Method
     */
    final public function implementsMethod(string $interface, string $methodName): Method
    {
        $method = Method::from([$interface, $methodName]);
        $method->setComment('{@inheritdoc}');
        $method->setBody(''); // Ensure that the body is not null

        foreach ($method->getParameters() as $parameter) {
            /** @var Type|null $type */
            $type = $parameter->getType(true);

            if ($type && $type->isClass()) {
                /** @psalm-suppress PossiblyNullArgument */
                $this->namespace->addUse($type->getSingleName());
            }
        }

        /** @var Type|null $returnType */
        $returnType = $method->getReturnType(true);

        if ($returnType && $returnType->isClass()) {
            /** @psalm-suppress PossiblyNullArgument */
            $this->namespace->addUse($returnType->getSingleName());
        }

        $this->class->addMember($method);

        return $method;
    }

    /**
     * Add use statement and simplify it
     *
     * @param class-string $type Type to use and simplify
     * @param string|null $alias Use alias
     *
     * @return string
     */
    final public function useAndSimplifyType(string $type, ?string $alias = null): string
    {
        return $this->namespace->addUse($type, $alias)->simplifyType($type);
    }

    /**
     * Add use statement
     *
     * @param class-string $type Type to use
     * @param string|null $alias Use alias
     *
     * @return self
     */
    final public function use(string $type, ?string $alias = null): self
    {
        $this->namespace->addUse($type, $alias);

        return $this;
    }

    /**
     * Generate the class code
     *
     * @return string
     */
    final public function generateClass(): string
    {
        return $this->printer->printClass($this->class, $this->namespace);
    }

    /**
     * Get the related printer instance
     *
     * @return Printer
     */
    final public function printer(): Printer
    {
        return $this->printer;
    }
}
