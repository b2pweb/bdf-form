<?php

namespace Bdf\Form\Child\Http;

/**
 * Extract HTTP fields value prefixed by a given string
 *
 * Note: Supports only array values
 *
 * <code>
 * $fields = new PrefixedHttpFields('child');
 *
 * $fields->extract(['child_foo' => 'bar', 'other_foo' => 'baz'], 'not found'); // => ['foo' => 'bar']
 * $fields->extract(['other' => 'value'], ['not found']); // => ['not found']
 * </code>
 */
final class PrefixedHttpFields implements HttpFieldsInterface
{
    /**
     * @var string
     */
    private $prefix;


    /**
     * ArrayOffsetHttpFields constructor.
     *
     * @param string $prefix The http fields prefix
     */
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($httpFields, $defaultValue)
    {
        $data = (array) $httpFields;
        $prefixLen = strlen($this->prefix);

        if ($prefixLen > 0) {
            $value = [];

            foreach ($data as $name => $datum) {
                if (str_starts_with($name, $this->prefix)) {
                    $value[substr($name, $prefixLen)] = $datum;
                }
            }
        } else {
            $value = $data;
        }

        // Return default value only if provided
        return $defaultValue !== null && empty($value) ? $defaultValue : $value;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($httpFields): bool
    {
        return true; // Always true ?
    }

    /**
     * {@inheritdoc}
     */
    public function format($value)
    {
        $http = [];

        foreach ($value as $field => $fieldValue) {
            $http[$this->prefix.$field] = $fieldValue;
        }

        return $http;
    }

    /**
     * {@inheritdoc}
     */
    public function get(?HttpFieldPath $path = null): HttpFieldPath
    {
        return $path === null ? HttpFieldPath::prefixed($this->prefix) : $path->prefix($this->prefix);
    }
}
