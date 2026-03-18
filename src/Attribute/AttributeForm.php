<?php

namespace Bdf\Form\Attribute;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\ConfigureFormBuilderStrategy;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Custom\CustomForm;

/**
 * Utility class for declare a form using PHP 8 attributes and declare elements using typed properties
 *
 * @template T
 * @extends CustomForm<T>
 *
 * @api
 */
abstract class AttributeForm extends CustomForm
{
    /**
     * Implementation use to process attributes and properties
     * and for configure the form builder
     *
     * @var AttributesProcessorInterface
     * @readonly
     */
    private AttributesProcessorInterface $processor;

    /**
     * Action to perform after the form was built
     *
     * @var PostConfigureInterface|null
     */
    private ?PostConfigureInterface $postConfigure = null;

    /**
     * @param FormBuilderInterface|null $builder The form builder using by CustomForm
     * @param AttributesProcessorInterface|null $processor The attributes processor.
     *     By default, use ReflectionProcessor with ConfigureFormBuilderStrategy as strategy
     */
    public function __construct(?FormBuilderInterface $builder = null, ?AttributesProcessorInterface $processor = null)
    {
        parent::__construct($builder);

        $this->processor = $processor ?? new ReflectionProcessor(new ConfigureFormBuilderStrategy());
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $this->postConfigure = $this->processor->configureBuilder($this, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function postConfigure(FormInterface $form): void
    {
        if ($this->postConfigure) {
            $this->postConfigure->postConfigure($this, $form);
            $this->postConfigure = null;
        }
    }
}
