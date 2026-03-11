<?php

namespace Bdf\Form\Child\Http;

use Override;

use function is_array;

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
final readonly class ArrayOffsetHttpFields implements HttpFieldsInterface
{
    public function __construct(
        /**
         * The HTTP field name
         */
        private string $offset,
    ) {}

    #[Override]
    public function extract(mixed $httpFields): mixed
    {
        if (!is_array($httpFields) || !isset($httpFields[$this->offset])) {
            return null;
        }

        return $httpFields[$this->offset];
    }

    #[Override]
    public function contains($httpFields): bool
    {
        return isset($httpFields[$this->offset]);
    }

    #[Override]
    public function format(mixed $value): array
    {
        return [$this->offset => $value];
    }

    #[Override]
    public function get(?HttpFieldPath $path = null): HttpFieldPath
    {
        return $path === null ? HttpFieldPath::named($this->offset) : $path->add($this->offset);
    }
}
