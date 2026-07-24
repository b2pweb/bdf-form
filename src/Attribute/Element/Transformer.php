<?php

namespace Bdf\Form\Attribute\Element;

use Attribute;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Attribute\Aggregate\ArrayTransformer;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\ObjectInstantiation;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Transformer\TransformerInterface;
use InvalidArgumentException;
use Nette\PhpGenerator\Literal;
use Override;

use function is_object;
use function is_string;
use function trigger_error;

/**
 * Add a transformer on the element, using a transformer class
 *
 * Note: it's preferred to use directly the transformer as attribute on the element property
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->transformer(new MyTransformer(...$arguments));
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Transformer(new MyTransformer('foo', 'bar'))]
 *     private IntegerElement $foo;
 *
 *     // Use a transformer loaded from the registry/container
 *     #[Transformer(TransformerService::class)]
 *     private IntegerElement $bar;
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ElementBuilderInterface::transformer() The called method
 * @see ArrayElementBuilder::arrayTransformer() The called method if array flag is set
 * @see CallbackTransformer For use custom methods as transformer instead of class
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Transformer implements ChildBuilderAttributeInterface
{
    /**
     * The transformer class name or instance
     *
     * @var class-string<TransformerInterface>|TransformerInterface
     */
    private readonly string|TransformerInterface $transformer;

    /**
     * @param class-string<TransformerInterface>|TransformerInterface|null $transformer
     * @param class-string<TransformerInterface>|TransformerInterface|null $transformerClass
     */
    public function __construct(
        /**
         * The transformer class name or instance
         *
         * @var class-string<TransformerInterface>|TransformerInterface|null
         */
        string|TransformerInterface|null $transformer  = null,
        /**
         * Arguments to provide on the transformer constructor.
         * Only used if first parameter is a class name, and the transformer is instantiable
         *
         * @var array
         * @deprecated Use object parameter instead
         */
        private readonly array $constructorArguments = [],
        /**
         * Apply the transformer on the whole array element
         * instead of each element
         *
         * If set to true, {@see ArrayElementBuilder::arrayTransformer()} will be used
         *
         * Note: this flag can be used only on array element
         *
         * @var bool
         * @readonly
         *
         * @see ArrayTransformer Prefer use this attribute for array element, instead of manually set this flag
         */
        private readonly bool $array = false,
        /**
         * @var class-string<TransformerInterface>|TransformerInterface
         * @deprecated For compatiblity only. Use first parameter instead.
         */
        string|TransformerInterface|null $transformerClass = null,
    ) {
        $transformer ??= $transformerClass;

        if ($transformerClass !== null) {
            @trigger_error('The transformerClass parameter is deprecated since 2.0, use transformer parameter instead', E_USER_DEPRECATED);
        }

        if ($transformer === null) {
            throw new InvalidArgumentException('The transformer parameter must not be null.');
        }

        $this->transformer = $transformer;

        if ($this->constructorArguments) {
            if (!is_string($this->transformer)) {
                throw new \InvalidArgumentException('Constructor arguments can be used only with transformer class name');
            }

            @trigger_error('The constructorArguments parameter is deprecated since 2.0, use object parameter instead', E_USER_DEPRECATED);
        }
    }

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        $transformer = $this->transformer;

        if (is_string($transformer)) {
            $shouldBeInstantiated = !empty($this->constructorArguments) || self::canBeInstantiatedWithoutParameters($transformer);

            if ($shouldBeInstantiated) {
                @trigger_error('Passing a transformer class instead of object for inline instantiation is deprecated since 2.0, will use the registry in 3.0. Use object instead.', E_USER_DEPRECATED);

                $transformer = new $transformer(...$this->constructorArguments);
            }
        }

        if ($this->array) {
            /** @var ChildBuilderInterface<ArrayElementBuilder> $builder */
            $builder->arrayTransformer($transformer);
        } else {
            $builder->transformer($transformer);
        }
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        $transformer = $this->transformer;

        if (is_object($transformer)) {
            $transformer = ObjectInstantiation::promotedProperties($transformer)->render($generator);
            $code = $this->array ? '$?->arrayTransformer(?);' : '$?->transformer(?);';
            $generator->line($code, [$name, $transformer]);
            return;
        }

        // Transformer is the class name
        $shouldBeInstantiated = !empty($this->constructorArguments) || self::canBeInstantiatedWithoutParameters($transformer);
        $transformer = $generator->useAndSimplifyType($transformer);

        if ($shouldBeInstantiated) {
            @trigger_error('Passing a transformer class instead of object for inline instantiation is deprecated since 2.0, will use the registry in 3.0. Use object instead.', E_USER_DEPRECATED);

            $code = $this->array ? '$?->arrayTransformer(new ?(...?));' : '$?->transformer(new ?(...?));';
            $generator->line($code, [$name, new Literal($transformer), $this->constructorArguments]);
            return;
        }

        $code = $this->array ? '$?->arrayTransformer(?::class);' : '$?->transformer(?::class);';
        $generator->line($code, [$name, new Literal($transformer)]);
    }

    /**
     * @param class-string $class
     * @return bool
     */
    private static function canBeInstantiatedWithoutParameters(string $class): bool
    {
        $r = new \ReflectionClass($class);

        if (!$r->isInstantiable()) {
            return false;
        }

        $constructor = $r->getConstructor();

        return $constructor === null || $constructor->getNumberOfRequiredParameters() === 0;
    }
}
