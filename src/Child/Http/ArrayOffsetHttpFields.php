<?php

namespace Bdf\Form\Child\Http;

/**
 * Extract HTTP fields value using a simple array offset
 * This is the default http fields implementation
 *
 * <code>
 * $fields = new ArrayOffsetHttpFields('child');
 *
 * $fields->extract(['child' => 'value'], 'not found'); // => 'value'
 * $fields->extract(['other' => 'value'], 'not found'); // => 'not found'
 * </code>
 */
final class ArrayOffsetHttpFields implements HttpFieldsInterface
{
    /**
     * @var string
     */
    private $offset;


    /**
     * ArrayOffsetHttpFields constructor.
     *
     * @param string $offset The field name
     */
    public function __construct(string $offset)
    {
        $this->offset = $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($httpFields)
    {
        if (!is_array($httpFields) || !isset($httpFields[$this->offset])) {
            return null;
        }

        return $httpFields[$this->offset];
    }

    /**
     * {@inheritdoc}
     */
    public function contains($httpFields): bool
    {
        return isset($httpFields[$this->offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function format($value)
    {
        return [$this->offset => $value];
    }

    /**
     * {@inheritdoc}
     */
    public function get(?HttpFieldPath $path = null): HttpFieldPath
    {
        return $path === null ? HttpFieldPath::named($this->offset) : $path->add($this->offset);
    }
}
