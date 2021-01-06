<?php

namespace Bdf\Form\Constraint;

use Bdf\Form\Util\FieldPath;
use Symfony\Component\Validator\Constraint;

/**
 * Add field option on comparison class
 * The class must extends a subclass of AbstractComparison
 */
trait FieldComparisonTrait
{
    /**
     * The field path
     *
     * @var string|FieldPath
     */
    public $field;

    public function __construct($field = null)
    {
        Constraint::__construct($field);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'field';
    }
}
