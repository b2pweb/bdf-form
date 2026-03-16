<?php

namespace Bdf\Form\Child\Http;

use Override;

use function str_starts_with;
use function strlen;
use function substr;

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
final readonly class PrefixedHttpFields implements HttpFieldsInterface
{
    public function __construct(
        /**
         * The http fields prefix
         */
        private string $prefix
    ) {}

    #[Override]
    public function extract(mixed $httpFields): array
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

        return $value;
    }

    #[Override]
    public function contains($httpFields): bool
    {
        return true; // Always true ?
    }

    #[Override]
    public function format(mixed $value): array
    {
        $http = [];

        foreach ((array) $value as $field => $fieldValue) {
            $http[$this->prefix.$field] = $fieldValue;
        }

        return $http;
    }

    #[Override]
    public function get(?HttpFieldPath $path = null): HttpFieldPath
    {
        return $path === null ? HttpFieldPath::prefixed($this->prefix) : $path->prefix($this->prefix);
    }
}
