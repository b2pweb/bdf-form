<?php

namespace Bdf\Form\Attribute\Element;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\CallbackModelTransformer;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\ClassGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\TransformerClassGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Factory;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PsrPrinter;
use Override;

use function is_object;
use function is_string;

/**
 * Add a HTTP transformer on the child element, by using method
 *
 * Transformation to entity and to input can be separated in two different method.
 * Those methods take the value and the input element as parameters, and should return the transformed value
 * If dedicated methods are not used, but the unified one, the third parameter is provided :
 * - on true the transformation is to the entity
 * - on false the transformation is to the input
 *
 * This attribute is equivalent to call :
 * <code>
 * // For unified callback
 * $builder->string('foo')->transformer([$this, 'myTransformer']);
 *
 * // When using two methods (toHttp: 'transformFooToHttp', fromHttp: 'transformFooFromHttp')
 * $builder->string('foo')->transformer(function ($value, ElementInterface $input, bool $toPhp) {
 *     return $toPhp ? $this->transformFooFromHttp($value, $input) : $this->transformFooToHttp($value, $input);
 * });
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[CallbackTransformer(fromHttp: 'fooFromHttp', toHttp: 'fooToHttp')]
 *     private IntegerElement $foo;
 *
 *     // With unified transformer (same as above)
 *     #[CallbackTransformer('barTransformer')]
 *     private IntegerElement $bar;
 *
 *     public function fooFromHttp(string $value, IntegerElement $input): int
 *     {
 *         return hexdec($value);
 *     }
 *
 *     public function fooToHttp(int $value, IntegerElement $input): string
 *     {
 *         return dechex($value);
 *     }
 *
 *     public function barTransformer($value, IntegerElement $input, bool $toPhp)
 *     {
 *         return $toPhp ? hexdec($value) : dechex($value);
 *     }
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ElementBuilderInterface::transformer() The called method
 * @see Transformer For use a transformer class as transformer
 * @see CallbackModelTransformer For use transformer in same way, but for model transformer intead of http one
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class CallbackTransformer implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * Method name use to define the unified transformer method
         * If defined, the other parameters will be ignored
         *
         * @var non-empty-string|null
         * @readonly
         */
        private ?string $callback = null,
        /**
         * Method name use to define the transformation process from http value to the input
         *
         * @var non-empty-string|null
         * @readonly
         */
        private ?string $fromHttp = null,
        /**
         * Method name use to define the transformation process from input value to http format
         *
         * @var non-empty-string|null
         * @readonly
         */
        private ?string $toHttp = null,
    ) {}

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        if ($this->callback !== null) {
            $callback = is_string($context)
                ? $context::{$this->callback}(...)
                : $context->{$this->callback}(...)
            ;
            $builder->transformer($callback);
            return;
        }

        $builder->transformer(new class ($context, $this->fromHttp, $this->toHttp) implements TransformerInterface {
            public function __construct(
                /**
                 * @var object|class-string
                 */
                private object|string $context,
                private ?string $fromHttp,
                private ?string $toHttp,
            ) {
            }

            #[Override]
            public function transformToHttp(mixed $value, ElementInterface $input): mixed
            {
                if ($this->toHttp === null) {
                    return $value;
                }

                return is_object($this->context)
                    ? $this->context->{$this->toHttp}($value, $input)
                    : $this->context::{$this->toHttp}($value, $input)
                ;
            }

            #[Override]
            public function transformFromHttp(mixed $value, ElementInterface $input): mixed
            {
                if ($this->fromHttp === null) {
                    return $value;
                }

                return is_object($this->context)
                    ? $this->context->{$this->fromHttp}($value, $input)
                    : $this->context::{$this->fromHttp}($value, $input)
                ;
            }
        });
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        if ($this->callback !== null) {
            if (is_object($context)) {
                $generator->line('$?->transformer($context->?(...));', [$name, $this->callback]);
            } else {
                $generator->line('$?->transformer(?::?(...));', [$name, new Literal($generator->useAndSimplifyType($context)), $this->callback]);
            }
            return;
        }

        $transformer = new TransformerClassGenerator($generator->namespace(), $generator->printer());

        $transformer->withPromotedProperty('context')->setPrivate();

        match (true) {
            $this->toHttp !== null && is_object($context) => $transformer->toHttp()->setBody('return $this->context->?($value, $input);', [$this->toHttp]),
            $this->toHttp !== null && is_string($context) => $transformer->toHttp()->setBody('return $this->context::?($value, $input);', [$this->toHttp]),
            default => $transformer->toHttp()->setBody('return $value;'),
        };

        match (true) {
            $this->fromHttp !== null && is_object($context) => $transformer->fromHttp()->setBody('return $this->context->?($value, $input);', [$this->fromHttp]),
            $this->fromHttp !== null && is_string($context) => $transformer->fromHttp()->setBody('return $this->context::?($value, $input);', [$this->fromHttp]),
            default => $transformer->fromHttp()->setBody('return $value;'),
        };

        $generator->line(
            '$?->transformer(new class ($context) ?);',
            [$name, new Literal($transformer->generateClass())]
        );
    }
}
