<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Filter\EmptyArrayValuesFilter;
use Bdf\Form\Registry\RegistryInterface;

/**
 * Child builder for ArrayElement
 *
 * @extends ChildBuilder<ArrayElementBuilder>
 */
class ArrayChildBuilder extends ChildBuilder
{
    /**
     * @var bool
     */
    private $filterEmptyValues = true;

    /**
     * ArrayChildBuilder constructor.
     *
     * @param string $name
     * @param ArrayElementBuilder $elementBuilder
     * @param RegistryInterface|null $registry
     */
    public function __construct(string $name, ElementBuilderInterface $elementBuilder, RegistryInterface $registry = null)
    {
        parent::__construct($name, $elementBuilder, $registry);

        $this->addFilterProvider([$this, 'provideEmptyValueFilter']);
    }

    /**
     * Enable or disable filtering empty values into the submitted array
     * By default this filter is enabled
     *
     * Note: are considered empty, the empty string '', the empty array [], and null
     *
     * @param bool $flag true to enable
     *
     * @return $this
     * @see EmptyArrayValuesFilter
     */
    public function filterEmptyValues(bool $flag = true): self
    {
        $this->filterEmptyValues = $flag;

        return $this;
    }

    protected function provideEmptyValueFilter(): array
    {
        if (!$this->filterEmptyValues) {
            return [];
        }

        return [EmptyArrayValuesFilter::instance()];
    }
}
