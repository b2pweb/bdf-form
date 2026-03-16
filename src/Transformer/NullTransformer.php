<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use Override;

/**
 * Null object for form transformer
 */
final readonly class NullTransformer implements TransformerInterface
{
    #[Override]
    public function transformToHttp(mixed $value, ElementInterface $input): mixed
    {
        return $value;
    }

    #[Override]
    public function transformFromHttp(mixed $value, ElementInterface $input): mixed
    {
        return $value;
    }

    /**
     * Get the null transformer instance
     *
     * @return static
     */
    public static function instance(): self
    {
        static $instance = new self();

        return $instance;
    }
}
