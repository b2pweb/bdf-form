<?php

namespace Bdf\Form\Leaf\Helper;

use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraints\Email;

/**
 * Provide email constraint builder for a StringElementBuilder
 *
 * <code>
 * $builder->email('contact')
 *     ->mode(Email::VALIDATION_MODE_LOOSE)
 *     ->errorMessage('Invalid email address')
 * ;
 * </code>
 *
 * @see EmailElement the built element
 */
class EmailElementBuilder extends StringElementBuilder
{
    /**
     * @var bool
     */
    private $useConstraint = true;

    private ?string $errorMessage = null;
    private ?string $mode = null;

    /**
     * @var (callable(string):string)|null
     */
    private $normalizer = null;

    /**
     * EmailElementBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(?RegistryInterface $registry = null)
    {
        parent::__construct($registry);

        $this->addConstraintsProvider([$this, 'createEmailConstraint']);
    }

    /**
     * The validation mode
     * See Email::VALIDATION_MODE_* constants
     *
     * @param string $mode
     *
     * @return $this
     *
     * @see Email::VALIDATION_MODE_HTML5
     * @see Email::VALIDATION_MODE_LOOSE
     * @see Email::VALIDATION_MODE_STRICT
     */
    public function mode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Define the invalid email error message
     *
     * @param string $message
     *
     * @return $this
     */
    public function errorMessage(string $message): self
    {
        $this->errorMessage = $message;

        return $this;
    }

    /**
     * Define the normalizer for the email value
     *
     * <code>
     * // Add normalizer for adding an email host if not provided
     * $builder->email('contact')->normalizer(function (string $value) {
     *     if (strpos($value, '@') === false) {
     *         $value .= '@example.com';
     *     }
     *
     *     return $value;
     * });
     * </code>
     *
     * @param callable(string):string $normalizer
     *
     * @return $this
     */
    public function normalizer(callable $normalizer): self
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * Disable the email verification constraint
     *
     * @return $this
     */
    public function disableConstraint(): self
    {
        $this->useConstraint = false;

        return $this;
    }

    /**
     * Define the email validation constraint options
     *
     * <code>
     * $builder->email('contact')->useConstraint(mode: Email::VALIDATION_MODE_HTML5, message: 'my error');
     * </code>
     *
     * @return $this
     *
     * @see Email for list of options
     */
    public function useConstraint(?string $message = null, ?string $mode = null, ?string $normalizer = null): self
    {
        $this->useConstraint = true;
        $this->errorMessage = $message;
        $this->mode = $mode;
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * @return \Symfony\Component\Validator\Constraint[]
     */
    protected function createEmailConstraint(RegistryInterface $registry): array
    {
        if (!$this->useConstraint) {
            return [];
        }

        return [
            new Email(
                message: $this->errorMessage,
                mode: $this->mode,
                normalizer: $this->normalizer
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new EmailElement($validator, $transformer, $this->getChoices());
    }
}
