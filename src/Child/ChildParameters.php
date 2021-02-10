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
    /**
     * The child name
     *
     * @var string
     */
    public $name;

    /**
     * The inner element instance
     *
     * @var ElementInterface
     */
    public $element;

    /**
     * Http Fields to use
     *
     * @var HttpFieldsInterface
     */
    public $fields;

    /**
     * @var FilterInterface[]
     */
    public $filters;

    /**
     * @var mixed
     */
    public $defaultValue;

    /**
     * @var HydratorInterface|null
     */
    public $hydrator;

    /**
     * @var ExtractorInterface|null
     */
    public $extractor;

    /**
     * Array of dependencies child names
     *
     * @var string[]
     */
    public $dependencies;

    /**
     * @var TransformerInterface|null
     */
    public $modelTransformer;

    /**
     * The child class name
     *
     * @var class-string<ChildInterface>
     */
    public $className;

    /**
     * The child instance
     * Set a value to ignore the default child instantiation on the ChildBuilder
     *
     * @var ChildInterface|null
     */
    public $child;

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
    public $factories = [];
}
