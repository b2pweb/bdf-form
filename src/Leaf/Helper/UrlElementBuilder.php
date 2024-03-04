<?php

namespace Bdf\Form\Leaf\Helper;

use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraints\Url;

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
    /**
     * @var bool
     */
    private $useConstraint = true;

    /**
     * @var array{message?:string,protocols?:string[],relativeProtocol?:bool,normalizer?:callable(string):string}
     */
    private $constraintOptions = [];

    /**
     * UrlElementBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(?RegistryInterface $registry = null)
    {
        parent::__construct($registry);

        $this->addConstraintsProvider([$this, 'createUrlConstraint']);
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
    public function protocols(string ...$protocols): self
    {
        $this->constraintOptions['protocols'] = $protocols;

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
    public function relativeProtocol(bool $enable = true): self
    {
        $this->constraintOptions['relativeProtocol'] = $enable;

        return $this;
    }

    /**
     * Define the invalid URL error message
     *
     * @param string $message
     *
     * @return $this
     */
    public function errorMessage(string $message): self
    {
        $this->constraintOptions['message'] = $message;

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
    public function normalizer(callable $normalizer): self
    {
        $this->constraintOptions['normalizer'] = $normalizer;

        return $this;
    }

    /**
     * Disable the url verification constraint
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
     * $builder->email('contact')->useConstraint(['protocols' => ['ssh', 'sftp'], 'message' => 'my error']);
     * </code>
     *
     * @param array{message?:string,protocols?:string[],relativeProtocol?:bool,normalizer?:callable(string):string} $options
     *
     * @return $this
     *
     * @see Url for list of options
     */
    public function useConstraint(array $options = []): self
    {
        $this->useConstraint = true;
        $this->constraintOptions = $options;

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

        return [new Url($this->constraintOptions)];
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new UrlElement($validator, $transformer, $this->getChoices());
    }
}
