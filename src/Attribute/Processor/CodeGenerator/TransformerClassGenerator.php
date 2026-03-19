<?php

namespace Bdf\Form\Attribute\Processor\CodeGenerator;

use Bdf\Form\Transformer\TransformerInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PromotedParameter;

/**
 * Generator for a transformer class
 *
 * @see TransformerInterface The implemented interface
 */
final class TransformerClassGenerator extends ClassGenerator
{
    private Method $toHttp;
    private Method $fromHttp;

    private ?Method $constructor = null;

    /**
     * @param PhpNamespace $namespace The used namespace, for use statements, and simplify types
     * @param Printer|null $printer Code printer
     */
    public function __construct(PhpNamespace $namespace, ?Printer $printer = null)
    {
        parent::__construct($namespace, new ClassType(), $printer);

        $this->implements(TransformerInterface::class);

        $this->toHttp = $this->implementsMethod(TransformerInterface::class, 'transformToHttp');
        $this->fromHttp = $this->implementsMethod(TransformerInterface::class, 'transformFromHttp');
    }

    /**
     * Add a new promoted property on constructor
     *
     * @param string $name The property name, without $
     *
     * @return PromotedParameter
     */
    public function withPromotedProperty(string $name): PromotedParameter
    {
        if (!$this->constructor) {
            $this->constructor = $this->class()->addMethod('__construct');
        }

        return $this->constructor->addPromotedParameter($name);
    }

    /**
     * Get the transformToHttp method builder
     *
     * @return Method
     *
     * @see TransformerInterface::transformToHttp()
     */
    public function toHttp(): Method
    {
        return $this->toHttp;
    }

    /**
     * Get the transformFromHttp method builder
     *
     * @return Method
     *
     * @see TransformerInterface::transformFromHttp()
     */
    public function fromHttp(): Method
    {
        return $this->fromHttp;
    }
}
