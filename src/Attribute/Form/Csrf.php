<?php

namespace Bdf\Form\Attribute\Form;

use Attribute;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Csrf\CsrfElementBuilder;

/**
 * Add a CSRF check on the form
 *
 * It's also possible to define a property of type `CsrfElement`, but configuration of message, tokenId and invalidate
 * will not be possible.
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->csrf('csrf')->message('Token invalide');
 * </code>
 *
 * Usage:
 * <code>
 * #[Csrf(name: 'csrf', message: 'Token invalide')]
 * class MyForm extends AttributeForm
 * {
 * }
 * </code>
 *
 * @see FormBuilderInterface::csrf() The called method
 * @see CsrfElementBuilder
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Csrf implements FormBuilderAttributeInterface
{
    public function __construct(
        /**
         * The token input name
         *
         * @var non-empty-string
         * @readonly
         */
        private string $name = '_token',
        /**
         * The token id
         * By default is value is the class name of `CsrfElement`
         *
         * @var string|null
         * @readonly
         *
         * @see CsrfTokenManagerInterface::getToken() The parameter tokenId will be used as parameter of this method
         * @see CsrfElementBuilder::tokenId() The called method if defined
         */
        private ?string $tokenId = null,
        /**
         * The error message to display if the token do not correspond
         *
         * @var string|null
         * @readonly
         *
         * @see CsrfElementBuilder::message() The called method if defined
         */
        private ?string $message = null,
        /**
         * Always invalidate the CSRF token after submission
         *
         * @var bool|null
         * @readonly
         *
         * @see CsrfElementBuilder::invalidate() The called method if defined
         */
        private ?bool $invalidate = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnFormBuilder(AttributeForm $form, FormBuilderInterface $builder): void
    {
        $csrf = $builder->csrf($this->name);

        if ($this->tokenId !== null) {
            $csrf->tokenId($this->tokenId);
        }

        if ($this->message !== null) {
            $csrf->message($this->message);
        }

        if ($this->invalidate !== null) {
            $csrf->invalidate($this->invalidate);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForFormBuilder(AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $parameters = [$this->name];
        $line = '$builder->csrf(?)';

        if ($this->tokenId !== null) {
            $line .= '->tokenId(?)';
            $parameters[] = $this->tokenId;
        }

        if ($this->message !== null) {
            $line .= '->message(?)';
            $parameters[] = $this->message;
        }

        if ($this->invalidate !== null) {
            $line .= '->invalidate(?)';
            $parameters[] = $this->invalidate;
        }

        $line .= ';';

        $generator->line($line, $parameters);
    }
}
