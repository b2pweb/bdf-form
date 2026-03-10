<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use Override;

/**
 * Null object for form transformer
 */
final class NullTransformer implements TransformerInterface
{
    /**
     * @var NullTransformer|null
     */
    private static $instance;

    #[Override]
    public function transformToHttp($value, ElementInterface $input)
    {
        return $value;
    }

    #[Override]
    public function transformFromHttp($value, ElementInterface $input)
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
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self();
    }
}
