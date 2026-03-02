<?php

namespace Bdf\Form\Constraint;

use Bdf\Form\Util\FieldPath;
use Symfony\Component\Validator\Constraint;

use function is_array;
use function sprintf;
use function trigger_error;

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

    /**
     * FieldComparisonTrait constructor.
     * @param string|FieldPath|array $field
     */
    public function __construct($field)
    {
        if (is_array($field)) {
            @trigger_error(sprintf('Passing an array of options to "%s" is deprecated since version 1.2 and will not be supported in 2.0. Use named arguments instead.', static::class), E_USER_DEPRECATED);

            $options = $field;
            $field = $options['field'] ?? null;
        }

        Constraint::__construct($options ?? null);

        $this->field = $field ?? $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): ?string
    {
        return 'field';
    }
}
