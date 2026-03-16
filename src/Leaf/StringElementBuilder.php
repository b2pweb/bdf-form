<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Override;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Builder for string element
 *
 * <code>
 * $builder->string('username')
 *     ->required()
 *     ->length(min: 6, max: 32)
 *     ->regex('/[a-z_-]+/i')
 * ;
 * </code>
 *
 * @see StringElement
 * @see FormBuilderInterface::string()
 *
 * @extends AbstractElementBuilder<StringElement>
 */
class StringElementBuilder extends AbstractElementBuilder
{
    use ChoiceBuilderTrait;

    /**
     * Add a string length constraint
     *
     * Usage:
     * <code>
     * $builder->length(max: 256);
     * </code>
     *
     * @param positive-int|null $exactly
     * @param non-negative-int|null $min
     * @param positive-int|null $max
     * @param value-of<Length::VALID_COUNT_UNITS>|null $countUnit
     *
     * @return $this
     *
     * @see Length For options
     */
    public function length(?int $exactly = null, ?int $min = null, ?int $max = null, ?string $charset = null, ?callable $normalizer = null, ?string $countUnit = null, ?string $exactMessage = null, ?string $minMessage = null, ?string $maxMessage = null, ?string $charsetMessage = null): static
    {
        return $this->satisfy(
            new Length(
                exactly: $exactly,
                min: $min,
                max: $max,
                charset: $charset,
                normalizer: $normalizer,
                countUnit: $countUnit,
                exactMessage: $exactMessage,
                minMessage: $minMessage,
                maxMessage: $maxMessage,
                charsetMessage: $charsetMessage,
            )
        );
    }

    /**
     * Add a regex constraint
     *
     * <code>
     * $builder->regex('/[a-z_-]+/'); // Simple regex
     * $builder->regex('/[a-z_-]+/', message: 'Invalid value'); // With custom options
     * </code>
     *
     * @param string $pattern
     *
     * @return $this
     *
     * @see Regex
     */
    public function regex(string $pattern, ?string $message = null, ?string $htmlPattern = null, ?bool $match = null, ?callable $normalizer = null): static
    {
        return $this->satisfy(
            new Regex(
                pattern: $pattern,
                message: $message,
                htmlPattern: $htmlPattern,
                match: $match,
                normalizer: $normalizer,
            )
        );
    }

    #[Override]
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): StringElement
    {
        return new StringElement($validator, $transformer, $this->getChoices());
    }
}
