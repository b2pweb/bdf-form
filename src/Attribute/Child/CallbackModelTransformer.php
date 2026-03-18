<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\TransformerClassGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PsrPrinter;

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
final class CallbackModelTransformer implements ChildBuilderAttributeInterface
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
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        if ($this->callback !== null) {
            $builder->modelTransformer([$form, $this->callback]);
            return;
        }

        $builder->modelTransformer(new class ($form, $this->toInput, $this->toEntity) implements TransformerInterface {
            public function __construct(
                private AttributeForm $form,
                private ?string $toInput,
                private ?string $toEntity,
            ) {
            }

            /**
             * {@inheritdoc}
             */
            public function transformToHttp($value, ElementInterface $input)
            {
                if ($this->toInput === null) {
                    return $value;
                }

                return $this->form->{$this->toInput}($value, $input);
            }

            /**
             * {@inheritdoc}
             */
            public function transformFromHttp($value, ElementInterface $input)
            {
                if ($this->toEntity === null) {
                    return $value;
                }

                return $this->form->{$this->toEntity}($value, $input);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        if ($this->callback !== null) {
            $generator->line('$?->modelTransformer([$form, ?]);', [$name, $this->callback]);
            return;
        }

        $transformer = new TransformerClassGenerator($generator->namespace(), $generator->printer());

        $transformer->withPromotedProperty('form')->setPrivate();

        if ($this->toInput !== null) {
            $transformer->toHttp()->setBody('return $this->form->?($value, $input);', [$this->toInput]);
        } else {
            $transformer->toHttp()->setBody('return $value;');
        }

        if ($this->toEntity !== null) {
            $transformer->fromHttp()->setBody('return $this->form->?($value, $input);', [$this->toEntity]);
        } else {
            $transformer->fromHttp()->setBody('return $value;');
        }

        $generator->line(
            '$?->modelTransformer(new class ($form) ?);',
            [$name, new Literal($transformer->generateClass())]
        );
    }
}
