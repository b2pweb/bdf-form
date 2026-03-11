<?php

namespace Bdf\Form\Leaf\Helper;

use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Override;
use Symfony\Component\Validator\Constraints\Url;

use function is_string;
use function property_exists;

/**
 * Provide URL constraint builder for a StringElementBuilder
 *
 * <code>
 * $builder->url('home')->protocols('https');
 * </code>
 *
 * @see UrlElement the built element
 */
class UrlElementBuilder extends StringElementBuilder
{
    private bool $useConstraint = true;
    private ?string $errorMessage = null;

    /**
     * @var string[]|null
     */
    private ?array $protocols = null;
    private ?bool $relativeProtocol = null;

    /**
     * @var (callable(string):string)|null
     */
    private mixed $normalizer = null;
    private ?bool $requireTld = false;
    private ?string $tldMessage = null;

    /**
     * UrlElementBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(?RegistryInterface $registry = null)
    {
        parent::__construct($registry);

        $this->addConstraintsProvider($this->createUrlConstraint(...));
    }

    /**
     * Define authorized protocols list
     *
     * <code>
     * $builder->url('home')->protocols('http', 'https');
     * </code>
     *
     * @param string ...$protocols
     *
     * @return $this
     */
    public function protocols(string ...$protocols): static
    {
        $this->protocols = $protocols;

        return $this;
    }

    /**
     * Enable relative protocol handling
     * URL without protocol like '//example.com' are accepted
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function relativeProtocol(bool $enable = true): static
    {
        $this->relativeProtocol = $enable;

        return $this;
    }

    /**
     * Define the invalid URL error message
     *
     * @param string $message
     *
     * @return $this
     */
    public function errorMessage(string $message): static
    {
        $this->errorMessage = $message;

        return $this;
    }

    /**
     * Define the normalizer for the URL value
     *
     * <code>
     * // Add normalizer handle relative URL
     * $builder->email('contact')->normalizer(function (string $value) {
     *     if (strpos($value, '://') === false) {
     *         $value = 'http://example.com/'.$value;
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
    public function normalizer(callable $normalizer): static
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * Disable the url verification constraint
     *
     * @return $this
     */
    public function disableConstraint(): static
    {
        $this->useConstraint = false;

        return $this;
    }

    /**
     * Define the email validation constraint options
     *
     * <code>
     * $builder->email('contact')->useConstraint(protocols: ['ssh', 'sftp'], message: 'my error');
     * </code>
     *
     * @return $this
     *
     * @see Url for list of options
     */
    public function useConstraint(?string $message = null, string|array|null $protocols = null, ?bool $relativeProtocol = null, ?callable $normalizer = null): static
    {
        $this->useConstraint = true;

        $this->errorMessage = $message;
        $this->protocols = is_string($protocols) ? [$protocols] : $protocols;
        $this->relativeProtocol = $relativeProtocol;
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * @return \Symfony\Component\Validator\Constraint[]
     */
    protected function createUrlConstraint(RegistryInterface $registry): array
    {
        if (!$this->useConstraint) {
            return [];
        }

        return [
            property_exists(Url::class, 'requireTld') // SF >= 7.1
                ? new Url(
                    message: $this->errorMessage,
                    protocols: $this->protocols,
                    relativeProtocol: $this->relativeProtocol,
                    normalizer: $this->normalizer,
                    requireTld: $this->requireTld,
                    tldMessage: $this->tldMessage
                )
                : new Url(
                    message: $this->errorMessage,
                    protocols: $this->protocols,
                    relativeProtocol: $this->relativeProtocol,
                    normalizer: $this->normalizer,
                )
            ,
        ];
    }

    #[Override]
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): UrlElement
    {
        return new UrlElement($validator, $transformer, $this->getChoices());
    }
}
