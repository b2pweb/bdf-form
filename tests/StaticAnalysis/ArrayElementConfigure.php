<?php

namespace Bdf\Form\StaticAnalysis;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;

class ArrayElementConfigure extends CustomForm
{
    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->array('values')->element(ArrayElement::class, function (ArrayElementBuilder $builder) {});
    }
}
