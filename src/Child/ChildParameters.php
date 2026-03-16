<?php

namespace Bdf\Form\Child;

use Bdf\Form\Child\Http\HttpFieldsInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Filter\FilterInterface;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Bdf\Form\Transformer\TransformerInterface;

/**
 * Parameters for create a child
 */
final class ChildParameters
{
    public function __construct(
        /**
         * The child name
         *
         * @var string
         */
        public string $name,

        /**
         * The inner element instance
         *
         * @var ElementInterface
         */
        public ElementInterface $element,

        /**
         * Http Fields to use
         *
         * @var HttpFieldsInterface
         */
        public HttpFieldsInterface $fields,

        /**
         * @var HydratorInterface|null
         */
        public ?HydratorInterface $hydrator,

        /**
         * @var ExtractorInterface|null
         */
        public ?ExtractorInterface $extractor,

        /**
         * Array of dependencies child names
         *
         * @var string[]
         */
        public array $dependencies,

        /**
         * @var TransformerInterface|null
         */
        public ?TransformerInterface $modelTransformer,

        /**
         * The child class name
         *
         * @var class-string<ChildInterface>
         */
        public string $className,

        /**
         * @var mixed
         */
        public mixed $defaultValue = null,

        /**
         * @var FilterInterface[]
         */
        public array $filters = [],

        /**
         * The child instance
         * Set a value to ignore the default child instantiation on the ChildBuilder
         *
         * @var ChildInterface|null
         */
        public ?ChildInterface $child = null,

        /**
         * List of child factories to apply
         * The return value of each factories will fill the $this->child field
         *
         * This parameter can be used to decorate a child instance like :
         * <code>
         * public function decorateChild(ChildParameters $parameters)
         * {
         *     $parameters->factories[] = function (ChildParameters $parameters) {
         *         return new MyChildWrapper($parameters->child);
         *     };
         * }
         * </code>
         *
         * @var (callable(ChildParameters):ChildInterface)[]
         */
        public array $factories = [],
    ) {}
}
