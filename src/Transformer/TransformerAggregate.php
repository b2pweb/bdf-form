<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use Override;

use function array_unshift;
use function count;
use function sprintf;
use function trigger_error;

/**
 * Aggregation of transformers
 *
 * - The transformers are applied in order for transform from PHP to HTTP value
 * - For transform from HTTP to PHP, the transformers are applied in reverse order
 */
final /*readonly*/ class TransformerAggregate implements TransformerInterface
{
    public function __construct(
        /**
         * @var TransformerInterface[]
         */
        private array $transformers,
    ) {}

    #[Override]
    public function transformToHttp(mixed $value, ElementInterface $input): mixed
    {
        foreach ($this->transformers as $transformer) {
            $value = $transformer->transformToHttp($value, $input);
        }

        return $value;
    }

    #[Override]
    public function transformFromHttp(mixed $value, ElementInterface $input): mixed
    {
        for ($i = count($this->transformers) - 1; $i >= 0; --$i) {
            $value = $this->transformers[$i]->transformFromHttp($value, $input);
        }

        return $value;
    }

    /**
     * Add a transformer at the head of the transformer list
     *
     * @param TransformerInterface $transformer
     * @deprecated since 2.0, the class will be marked as readonly in 3.0
     */
    public function prepend(TransformerInterface $transformer): void
    {
        @trigger_error(sprintf('Modifying "%s" is deprecated since 2.0, the class will be marked as readonly in 3.0.', self::class), E_USER_DEPRECATED);
        array_unshift($this->transformers, $transformer);
    }

    /**
     * Add a transformer at the end of the transformer list
     *
     * @param TransformerInterface $transformer
     * @deprecated since 2.0, the class will be marked as readonly in 3.0
     */
    public function append(TransformerInterface $transformer): void
    {
        @trigger_error(sprintf('Modifying "%s" is deprecated since 2.0, the class will be marked as readonly in 3.0.', self::class), E_USER_DEPRECATED);
        $this->transformers[] = $transformer;
    }
}
