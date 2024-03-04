<?php

namespace Bdf\Form\Aggregate;

use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Csrf\CsrfElementBuilder;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\BooleanElementBuilder;
use Bdf\Form\Leaf\Date\DateTimeChildBuilder;
use Bdf\Form\Leaf\Date\DateTimeElementBuilder;
use Bdf\Form\Leaf\FloatElementBuilder;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Phone\PhoneChildBuilder;
use Bdf\Form\Phone\PhoneElementBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Base builder type for a form
 *
 * <code>
 * // Define the generated entity
 * $builder->generates(MyEntity::class);
 *
 * // Declare fields
 * $builder->string('foo')->required()->setter();
 * $builder->integer('bar')->min(11)->required()->setter();
 *
 * // Build the form
 * $form = $builder->buildElement();
 * </code>
 *
 * @extends ElementBuilderInterface<FormInterface>
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
     * @param non-empty-string $name The child name
     * @param class-string<E> $element The element class name. May be a custom form
     *
     * @template E as ElementInterface
     *
     * @return ChildBuilderInterface<ElementBuilderInterface<E>> The child builder
     */
    public function add(string $name, string $element): ChildBuilderInterface;

    /**
     * Add a new string element on the form
     *
     * <code>
     * $builder->string('id', 'aaa-aaa-aaa')->regex('/[a-z]{3}(-[a-z]{3}){2}/i')->length(['max' => 35]);
     * </code>
     *
     * @param non-empty-string $name The child name
     * @param string|null $default Default value to submit
     *
     * @return ChildBuilder|StringElementBuilder
     * @psalm-return ChildBuilderInterface<StringElementBuilder>
     */
    public function string(string $name, ?string $default = null): ChildBuilderInterface;

    /**
     * Add a new integer element on the form
     *
     * <code>
     * $builder->integer('value', 42)->min(6)->max(66);
     * </code>
     *
     * @param non-empty-string $name The child name
     * @param integer|null $default Default value to submit
     *
     * @return ChildBuilder|IntegerElementBuilder
     * @psalm-return ChildBuilderInterface<IntegerElementBuilder>
     */
    public function integer(string $name, ?int $default = null): ChildBuilderInterface;

    /**
     * Add a new float element on the form
     *
     * <code>
     * $builder->float('value', 42)->scale(2);
     * </code>
     *
     * @param non-empty-string $name The child name
     * @param float|null $default Default value to submit
     *
     * @return ChildBuilder|FloatElementBuilder
     * @psalm-return ChildBuilderInterface<FloatElementBuilder>
     */
    public function float(string $name, ?float $default = null): ChildBuilderInterface;

    /**
     * Add a new boolean element on the form
     *
     * <code>
     * $builder->boolean('accept');
     * </code>
     *
     * @param non-empty-string $name The child name
     *
     * @return ChildBuilder|BooleanElementBuilder
     * @psalm-return ChildBuilderInterface<BooleanElementBuilder>
     */
    public function boolean(string $name): ChildBuilderInterface;

    /**
     * Add a new date time element on the form
     *
     * <code>
     * $builder->dateTime('eventDate')->after(new DateTime());
     * </code>
     *
     * @param non-empty-string $name The child name
     *
     * @return DateTimeChildBuilder|DateTimeElementBuilder
     * @psalm-return ChildBuilderInterface<DateTimeElementBuilder>
     */
    public function dateTime(string $name): ChildBuilderInterface;

    /**
     * Add a new phone element on the form
     * The phone element will return a `\libphonenumber\PhoneNumber` instance
     *
     * Note: The package "giggsey/libphonenumber-for-php" is required to use this element
     *
     * <code>
     * $builder->phone('contact')
     *     ->allowInvalidNumber()
     *     ->setter()
     *     ->saveAsString()
     * ;
     * </code>
     *
     * @param non-empty-string $name The child name
     *
     * @return PhoneChildBuilder|PhoneElementBuilder
     * @psalm-return ChildBuilderInterface<PhoneElementBuilder>
     */
    public function phone(string $name): ChildBuilderInterface;

    /**
     * Add a new csrf token on form
     *
     * <code>
     * $builder->csrf()->tokenId('my_csrf_token');
     * </code>
     *
     * @param non-empty-string $name The child name
     *
     * @return ChildBuilder|CsrfElementBuilder
     * @psalm-return ChildBuilderInterface<CsrfElementBuilder>
     */
    public function csrf(string $name = '_token'): ChildBuilderInterface;

    /**
     * Add an embedded form
     *
     * <code>
     * // Takes the builder as parameter
     * // The builder is a child builder that wrap a FormBuilderInterface
     * $builder->embedded('emb', function (ChildBuilderInterface $builder) {
     *     $builder->string('foo');
     * });
     *
     * // Use without configurator callback
     * $embedded = $builder->embedded('emb');
     * $embedded->string('foo');
     * $embedded->string('bar');
     * </code>
     *
     * Note: It's advisable sur create a custom form, and add as embedded using `$this->add($name, $formType)` than use embedded()
     *
     * @param non-empty-string $name The child name
     * @param callable|null $configurator Configure the embedded form
     *
     * @return ChildBuilder|FormBuilder
     * @psalm-return ChildBuilderInterface<FormBuilder>
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
     * @param non-empty-string $name The child name
     * @param class-string<ElementInterface>|null $elementType The inner element type
     * @param callable|null $elementConfigurator Callback for configure the inner element
     *
     * @return ArrayChildBuilder|ArrayElementBuilder
     * @psalm-return ChildBuilderInterface<ArrayElementBuilder>
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
     * @param non-empty-string $name The button name
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
     * Prefer use the `generates()` method if possible
     *
     * @param ValueGeneratorInterface $generator
     *
     * @return $this
     *
     * @see FormBuilderInterface::generates()
     */
    public function generator(ValueGeneratorInterface $generator): FormBuilderInterface;

    /**
     * Define the entity to generate by the form when call $form->value()
     *
     * Note: This method is ignored when calling `ElementInterface::import()`, `FormInterface::attach()`, or `ElementBuilderInterface::value()`
     *
     * <code>
     * $builder->string('firstName')->setter();
     * $builder->string('lastName')->setter();
     *
     * $builder->generates(Person::class); // The Person's default constructor will be called, and the instance will be fill()'ed
     * $builder->generates($person); // Clone the $person instance, and fill will form values
     * $builder->generate(function (FormInterface $form) {
     *     return new Person(['myValue' => 42]); // Use custom generator
     * });
     * </code>
     *
     * @param callable|class-string|object|array $entity The entity to generate
     *
     * @return $this
     *
     * @see ValueGenerator
     * @see ElementInterface::value()
     */
    public function generates($entity): FormBuilderInterface;

    /**
     * Mark the form as optional
     * An optional form will not be validated if the form is empty, and its value will be null
     *
     * <code>
     * $builder->string('firstName')->required()->setter();
     * $builder->string('lastName')->required()->setter();
     * $builder->optional();
     *
     * $form = $builder->buildElement();
     *
     * $form->submit([])->valid(); // true
     * $form->value(); // null
     *
     * $form->submit(['firstName' => 'John'])->valid(); // false : lastName is required
     * </code>
     *
     * @param bool $flag true to mark the form as optional, false otherwise
     *
     * @return $this
     */
    public function optional(bool $flag = true): FormBuilderInterface;

    /**
     * {@inheritdoc}
     *
     * @return FormInterface
     */
    public function buildElement(): ElementInterface;
}
