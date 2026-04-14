<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\TransformerClassGenerator;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Nette\PhpGenerator\Literal;
use Override;

use function is_object;
use function is_string;

/**
 * Add a model transformer on the child element, by using method
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
 * $builder->string('foo')->modelTransformer([$this, 'myTransformer']);
 *
 * // When using two methods (toEntity: 'transformFooToEntity', toInput: 'transformFooToInput')
 * $builder->string('foo')->modelTransformer(function ($value, ElementInterface $input, bool $toEntity) {
 *     return $toEntity ? $this->transformFooToEntity($value, $input) : $this->transformFooToInput($value, $input);
 * });
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[CallbackModelTransformer(toEntity: 'fooToModel', toInput: 'fooToInput')]
 *     private IntegerElement $foo;
 *
 *     // With unified transformer (same as above)
 *     #[CallbackModelTransformer('barTransformer')]
 *     private IntegerElement $bar;
 *
 *     public function fooToModel(int $value, IntegerElement $input): string
 *     {
 *         return dechex($value);
 *     }
 *
 *     public function fooToInput(string $value, IntegerElement $input): int
 *     {
 *         return hexdec($value);
 *     }
 *
 *     public function barTransformer($value, IntegerElement $input, bool $toEntity)
 *     {
 *         return $toEntity ? dechex($value) : hexdec($value);
 *     }
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChildBuilderInterface::modelTransformer() The called method
 * @see ModelTransformer For use a transformer class as model transformer
 * @see CallbackTransformer For use transformer in same way, but for http transformer intead of model one
 * @see AsModelTransformer To annotate the method instead
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class CallbackModelTransformer implements ChildBuilderAttributeInterface
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
         * Method name use to define the transformation process from input value to the entity
         *
         * @var non-empty-string|null
         * @readonly
         */
        private ?string $toEntity = null,
        /**
         * Method name use to define the transformation process from entity value to input
         *
         * @var non-empty-string|null
         * @readonly
         */
        private ?string $toInput = null,
    ) {}

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        if ($this->callback !== null) {
            $callback = is_string($context)
                ? $context::{$this->callback}(...)
                : $context->{$this->callback}(...)
            ;

            $builder->modelTransformer($callback);
            return;
        }

        $builder->modelTransformer(new readonly class ($context, $this->toInput, $this->toEntity) implements TransformerInterface {
            public function __construct(
                /**
                 * @var object|class-string
                 */
                private object|string $context,
                private ?string $toInput,
                private ?string $toEntity,
            ) {}

            #[Override]
            public function transformToHttp(mixed $value, ElementInterface $input): mixed
            {
                if ($this->toInput === null) {
                    return $value;
                }

                return is_string($this->context)
                    ? $this->context::{$this->toInput}($value, $input)
                    : $this->context->{$this->toInput}($value, $input)
                ;
            }

            #[Override]
            public function transformFromHttp(mixed $value, ElementInterface $input): mixed
            {
                if ($this->toEntity === null) {
                    return $value;
                }

                return is_string($this->context)
                    ? $this->context::{$this->toEntity}($value, $input)
                    : $this->context->{$this->toEntity}($value, $input)
                ;
            }
        });
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        if ($this->callback !== null) {
            if (is_object($context)) {
                $generator->line('$?->modelTransformer($context->?(...));', [$name, $this->callback]);
            } else {
                $generator->line('$?->modelTransformer(?::?(...));', [$name, new Literal($generator->useAndSimplifyType($context)), $this->callback]);
            }
            return;
        }

        $transformer = new TransformerClassGenerator($generator->namespace(), $generator->printer());

        $transformer->withPromotedProperty('context')->setPrivate();

        match (true) {
            $this->toInput !== null && is_object($context) => $transformer->toHttp()->setBody('return $this->context->?($value, $input);', [$this->toInput]),
            $this->toInput !== null && is_string($context) => $transformer->toHttp()->setBody('return $this->context::?($value, $input);', [$this->toInput]),
            default => $transformer->toHttp()->setBody('return $value;'),
        };

        match (true) {
            $this->toEntity !== null && is_object($context) => $transformer->fromHttp()->setBody('return $this->context->?($value, $input);', [$this->toEntity]),
            $this->toEntity !== null && is_string($context) => $transformer->fromHttp()->setBody('return $this->context::?($value, $input);', [$this->toEntity]),
            default => $transformer->fromHttp()->setBody('return $value;'),
        };

        $generator->line(
            '$?->modelTransformer(new class ($context) ?);',
            [$name, new Literal($transformer->generateClass())]
        );
    }
}
