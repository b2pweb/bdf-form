<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

use function is_array;
use function property_exists;
use function sprintf;
use function trigger_error;

/**
 * Builder for string element
 *
 * <code>
 * $builder->string('username')
 *     ->required()
 *     ->length(['min' => 6, 'max' => 32])
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
     * Options are keys are : min, max, minMessage, maxMessage
     *
     * Usage:
     * <code>
     * $builder->length(['max' => 256]);
     * </code>
     *
     * @param array|int|null $exactly
     *
     * @return $this
     *
     * @see Length For options
     */
    public function length($exactly = null, ?int $min = null, ?int $max = null, ?string $charset = null, ?callable $normalizer = null, ?string $countUnit = null, ?string $exactMessage = null, ?string $minMessage = null, ?string $maxMessage = null, ?string $charsetMessage = null): self
    {
        static $sfVersion = null;

        if ($sfVersion === null) {
            if ((new \ReflectionClass(Length::class))->getConstructor()->getNumberOfParameters() === 1) {
                $sfVersion = 43;
            } elseif (!property_exists(Length::class, 'countUnit')) {
                // Count unit parameter has been added in SF 6.3
                $sfVersion = 53;
            } else {
                $sfVersion = 63;
            }
        }

        if (is_array($exactly)) {
            @trigger_error(sprintf('Passing an array of options on %s is deprecated since 1.7 and will be removed on 2.0, use named arguments instead.', __METHOD__), E_USER_DEPRECATED);

            $min ??= $exactly['min'] ?? null;
            $max ??= $exactly['max'] ?? null;
            $charset ??= $exactly['charset'] ?? null;
            $normalizer ??= $exactly['normalizer'] ?? null;
            $countUnit ??= $exactly['countUnit'] ?? null;
            $exactMessage ??= $exactly['exactMessage'] ?? null;
            $minMessage ??= $exactly['minMessage'] ?? null;
            $maxMessage ??= $exactly['maxMessage'] ?? null;
            $charsetMessage ??= $exactly['charsetMessage'] ?? null;
            $exactly = $exactly['exactly'] ?? null;
        }

        switch ($sfVersion) {
            case 43:
                $constraint = new Length(['value' => $exactly, 'min' => $min, 'max' => $max, 'charset' => $charset, 'normalizer' => $normalizer, 'exactMessage' => $exactMessage, 'minMessage' => $minMessage, 'maxMessage' => $maxMessage, 'charsetMessage' => $charsetMessage]);
                break;

            case 53:
                $constraint = new Length($exactly, $min, $max, $charset, $normalizer, $exactMessage, $minMessage, $maxMessage, $charsetMessage);
                break;

            default:
                $constraint = new Length($exactly, $min, $max, $charset, $normalizer, $countUnit, $exactMessage, $minMessage, $maxMessage, $charsetMessage);
        }

        return $this->satisfy($constraint);
    }

    /**
     * Add a regex constraint
     *
     * <code>
     * $builder->regex('/[a-z_-]+/'); // Simple regex
     * $builder->regex('/[a-z_-]+/', message: 'Invalid value'); // With custom options
     * </code>
     *
     * @param string|array $pattern
     *
     * @return $this
     *
     * @see Regex
     */
    public function regex($pattern, ?string $message = null, ?string $htmlPattern = null, ?bool $match = null, ?callable $normalizer = null): self
    {
        if (is_array($pattern)) {
            @trigger_error(sprintf('Passing an array of options on %s is deprecated since 1.7 and will be removed on 2.0, use named arguments instead.', __METHOD__), E_USER_DEPRECATED);

            $message ??= $pattern['message'] ?? null;
            $htmlPattern ??= $pattern['htmlPattern'] ?? null;
            $match ??= $pattern['match'] ?? null;
            $normalizer ??= $pattern['normalizer'] ?? null;
            $pattern = $pattern['pattern'] ?? null;
        }

        static $ifSf4 = null;

        if ($ifSf4 === null) {
            $ifSf4 = (new \ReflectionClass(Regex::class))->getConstructor()->getNumberOfParameters() === 1;
        }

        return $this->satisfy($ifSf4
            ? new Regex(['pattern' => $pattern, 'message' => $message, 'htmlPattern' => $htmlPattern, 'match' => $match, 'normalizer' => $normalizer])
            : new Regex($pattern, $message, $htmlPattern, $match, $normalizer)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new StringElement($validator, $transformer, $this->getChoices());
    }
}
