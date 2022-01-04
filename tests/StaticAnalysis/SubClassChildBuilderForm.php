<?php

namespace Bdf\Form\StaticAnalysis;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;

class SubClassChildBuilderForm extends CustomForm
{
    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->dateTime('foo')
            ->after(new \DateTime('+1d'))
            ->beforeField('bar')
            ->saveAsTimestamp()
            ->getter()->setter()
        ;

        $builder->dateTime('bar');
        $builder->phone('baz')
            ->region('FR')
            ->allowInvalidNumber(true)
            ->saveAsString()
            ->setter()
        ;
    }
}
