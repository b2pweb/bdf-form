<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Csrf\CsrfElementBuilder;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\BooleanElementBuilder;
use Bdf\Form\Leaf\FloatElementBuilder;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElementBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Builder for a form
 *
 * @see Form
 */
interface FormBuilderInterface extends ElementBuilderInterface
{
    /**
     * Add a new child element
     *
     * <code>
     * $builder->add('value', IntegerElement::class)->required()->min(5);
     * $builder->add('embed', MyEmbeddedForm::class)->required();
     * </code>
     *
     * @param string $name The child name
     * @param string $element The element class name
     *
     * @return ChildBuilderInterface The child builder
     */
    public function add(string $name, string $element): ChildBuilderInterface;

    /**
     * Add a new string element on the form
     *
     * <code>
     * $builder->string('id', 'aaa-aaa-aaa')->regex('/[a-z]{3}(-[a-z]{3}){2}/i')->length(['max' => 35]);
     * </code>
     *
     * @param string $name The child name
     * @param mixed $default Default value to submit (in HTTP format)
     *
     * @return ChildBuilderInterface|StringElementBuilder
     */
    public function string(string $name, $default = null): ChildBuilderInterface;

    /**
     * Add a new integer element on the form
     *
     * <code>
     * $builder->integer('value', 42)->min(6)->max(66);
     * </code>
     *
     * @param string $name The child name
     * @param mixed $default Default value to submit (in HTTP format)
     *
     * @return ChildBuilderInterface|IntegerElementBuilder
     */
    public function integer(string $name, $default = null): ChildBuilderInterface;

    /**
     * Add a new float element on the form
     *
     * <code>
     * $builder->float('value', 42)->scale(2);
     * </code>
     *
     * @param string $name The child name
     * @param mixed $default Default value to submit (in HTTP format)
     *
     * @return ChildBuilderInterface|FloatElementBuilder
     */
    public function float(string $name, $default = null): ChildBuilderInterface;

    /**
     * Add a new boolean element on the form
     *
     * <code>
     * $builder->boolean('accept', 42);
     * </code>
     *
     * @param string $name The child name
     *
     * @return ChildBuilderInterface|BooleanElementBuilder
     */
    public function boolean(string $name): ChildBuilderInterface;

    /**
     * Add a new csrf token on form
     *
     * <code>
     * $builder->csrf()->tokenId('my_csrf_token');
     * </code>
     *
     * @param string $name The child name
     *
     * @return ChildBuilderInterface|CsrfElementBuilder
     */
    public function csrf(string $name = '_token'): ChildBuilderInterface;

    /**
     * Add an embedded form
     *
     * <code>
     * $builder->embedded('emb', function ($builder) {
     *     $builder->string('foo');
     * });
     * </code>
     *
     * Note: It's advisable sur create a custom form, and add as embedded using `$this->add($name, $formType)` than use embedded()
     *
     * @param string $name The child name
     * @param callable|null $configurator Configure the embedded form
     *
     * @return ChildBuilderInterface|FormBuilder
     */
    public function embedded(string $name, ?callable $configurator = null): ChildBuilderInterface;

    /**
     * Add a new array element on the form
     *
     * <code>
     * $builder->array('names')->string()->length(['min' => 2]);
     * $builder->array('values', IntegerElement::class, function (IntegerElementBuilder $builder) {
     *     $builder->min(2);
     * });
     * </code>
     *
     * @param string $name The child name
     * @param string|null $elementType The inner element type
     * @param callable|null $elementConfigurator Callback for configure the inner element
     *
     * @return ChildBuilderInterface|ArrayElementBuilder
     *
     * @see ArrayElementBuilder::element() For the $elementType and $elementConfigurator parameters
     */
    public function array(string $name, ?string $elementType = null, ?callable $elementConfigurator = null): ChildBuilderInterface;

    /**
     * Add a submit button to the form
     *
     * <code>
     * $builder->submit('saveAndContinue');
     * </code>
     *
     * Note: The submit button only applies on the root form
     *
     * @param string $name The button name
     *
     * @return ButtonBuilderInterface
     */
    public function submit(string $name): ButtonBuilderInterface;

    /**
     * Set property accessor for the root form
     *
     * Note: Only applies on the root form
     *
     * @param PropertyAccessorInterface $propertyAccessor
     *
     * @return $this
     */
    public function propertyAccessor(PropertyAccessorInterface $propertyAccessor): FormBuilderInterface;

    /**
     * Set the validator for the root form
     *
     * Note: Only applies on the root form
     *
     * @param ValidatorInterface $validator
     *
     * @return $this
     */
    public function validator(ValidatorInterface $validator): FormBuilderInterface;

    /**
     * Define the value generator
     *
     * @param ValueGeneratorInterface $generator
     *
     * @return $this
     */
    public function generator(ValueGeneratorInterface $generator): FormBuilderInterface;

    /**
     * Define the entity to generate by the form when call $form->value()
     *
     * <code>
     * $builder->string('firstName')->setter();
     * $builder->string('lastName')->setter();
     *
     * $builder->generates(Person::class); // The Person's default constructor will be called, and the instance will be fill'ed
     * $builder->generates($person); // Clone the $person instance, and fill will form values
     * $builder->generate(function (EmbeddedFormInterface $form) {
     *     return new Person(['myValue' => 42]); // Use custom generator
     * });
     * </code>
     *
     * @param callable|string|object $entity The entity to generate
     *
     * @return $this
     *
     * @see ValueGenerator
     * @see ElementInterface::value()
     */
    public function generates($entity): FormBuilderInterface;

    /**
     * {@inheritdoc}
     *
     * @return FormInterface
     */
    public function buildElement(): ElementInterface;
}
