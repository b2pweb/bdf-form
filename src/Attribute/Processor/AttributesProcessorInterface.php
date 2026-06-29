<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;

/**
 * Process attributes of the form class to configure the inner form
 *
 * @api
 */
interface AttributesProcessorInterface
{
    /**
     * Configure the form builder
     *
     * @param object|class-string $context The form or DTO class name to analyze
     * @param FormBuilderInterface $builder Builder to configure
     *
     * @return PostConfigureInterface|null The post configuration action to perform
     *
     * @see AttributeForm::configure() Should be called in this method
     */
    public function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface;
}
