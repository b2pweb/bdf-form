<?php

namespace Bdf\Form\StaticAnalysis;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;

/**
 * @extends CustomForm<array>
 */
class ArrayElementConfigure extends CustomForm
{
    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->array('values')->element(
            ArrayElement::class,
            /**
             * @param ArrayElementBuilder<mixed> $builder
             */
            function (ArrayElementBuilder $builder) {}
        );
    }
}
