<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;

/**
 * Action to perform after the form was built
 *
 * @see AttributeForm::postConfigure()
 */
interface PostConfigureInterface
{
    /**
     * Perform the post configuration action
     *
     * @param AttributeForm $form The configured form instance
     * @param FormInterface $inner The inner built form
     *
     * @return void
     */
    public function postConfigure(AttributeForm $form, FormInterface $inner): void;
}
