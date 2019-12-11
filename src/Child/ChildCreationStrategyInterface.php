<?php

namespace Bdf\Form\Child;

use Bdf\Form\Child\Http\HttpFieldsInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Filter\FilterInterface;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;

/**
 * Invokable interface for define the child factory
 */
interface ChildCreationStrategyInterface
{
    /**
     * Instantiate the child
     *
     * @param string $name
     * @param ElementInterface $element
     * @param HttpFieldsInterface $fields
     * @param FilterInterface[] $filters
     * @param mixed $defaultValue
     * @param HydratorInterface|null $hydrator
     * @param ExtractorInterface|null $extractor
     * @param string[] $dependencies
     *
     * @return ChildInterface
     */
    public function __invoke(string $name, ElementInterface $element, HttpFieldsInterface $fields, array $filters, $defaultValue, ?HydratorInterface $hydrator, ?ExtractorInterface $extractor, array $dependencies): ChildInterface;
}
