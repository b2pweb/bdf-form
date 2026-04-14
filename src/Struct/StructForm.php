<?php

namespace Bdf\Form\Struct;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Custom\CustomForm;
use Override;

/**
 * Use a simple struct class as form
 *
 * Properties of the struct will be automatically mapped to form fields,
 * and hydrated when calling {@see FormInterface::value()}.
 *
 * @template T as object
 * @extends CustomForm<T>
 */
final class StructForm extends CustomForm
{
    private readonly AttributesProcessorInterface $processor;

    public function __construct(
        /**
         * The struct class name
         *
         * @var class-string<T>
         */
        private readonly string $class,
        ?FormBuilderInterface $builder = null,
        ?AttributesProcessorInterface $processor = null,
    )
    {
        parent::__construct($builder);

        $this->processor = $processor ?? new StructAttributesProcessorFactory()->runtime();
    }

    #[Override]
    protected function configure(FormBuilderInterface $builder): void
    {
        $this->processor->configureBuilder($this->class, $builder);
    }
}
