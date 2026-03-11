<?php

namespace Bdf\Form\Aggregate;

use BadMethodCallException;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\RootElementInterface;
use Bdf\Form\Util\RootFlagsTrait;
use Bdf\Form\View\ElementViewInterface;
use Iterator;
use LogicException;
use OutOfBoundsException;
use Override;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use WeakReference;

use function assert;

/**
 * Adapt a form element as root element
 * The root form handle constraint group, validator and property accessor instances, and submit button
 *
 * The root form should be used instead of the form element for `submit()`
 *
 * <code>
 * $form = new MyForm();
 *
 * $root = $form->root();
 * if (!$root->submit($request->post())->valid()) {
 *     throw new MyError();
 * }
 *
 * $entity = $root->value();
 *
 * switch ($btn = $root->submitButton() ? $btn->name() : null) {
 *     case 'save':
 *         return $this->save($entity);
 *
 *     case 'delete':
 *         return $this->delete($entity);
 *
 *     default:
 *         throw new InvalidAction();
 * }
 * </code>
 *
 * @todo delegation trait
 * @implements ChildAggregateInterface<mixed>
 */
final class RootForm implements RootElementInterface, ChildAggregateInterface
{
    use RootFlagsTrait;

    /**
     * @var WeakReference<Form>
     */
    private readonly WeakReference $form;

    /**
     * @var array<non-empty-string, ButtonInterface>
     */
    private readonly array $buttons;
    private ?ButtonInterface $submitButton = null;
    private ?PropertyAccessorInterface $propertyAccessor = null;
    private ?ValidatorInterface $validator = null;

    /**
     * RootForm constructor.
     *
     * @param Form $form
     * @param array<non-empty-string, ButtonInterface> $buttons Buttons, indexed by there name
     * @param PropertyAccessorInterface|null $propertyAccessor
     * @param ValidatorInterface|null $validator
     */
    public function __construct(Form $form, array $buttons = [], ?PropertyAccessorInterface $propertyAccessor = null, ?ValidatorInterface $validator = null)
    {
        $this->form = WeakReference::create($form);
        $this->buttons = $buttons;
        $this->propertyAccessor = $propertyAccessor;
        $this->validator = $validator;
    }

    #[Override]
    public function submit(mixed $data): static
    {
        $this->submitToButtons($data);
        $this->form->get()?->submit($data);

        return $this;
    }

    #[Override]
    public function patch(mixed $data): static
    {
        $this->submitToButtons($data);
        $this->form->get()?->patch($data);

        return $this;
    }

    #[Override]
    public function import(mixed $entity): static
    {
        $this->form->get()?->import($entity);

        return $this;
    }

    #[Override]
    public function value(): mixed
    {
        return $this->form->get()?->value();
    }

    #[Override]
    public function httpValue(): mixed
    {
        $httpValue = $this->form->get()?->httpValue();

        if (empty($this->buttons)) {
            return $httpValue;
        }

        $httpValue = (array) $httpValue;

        foreach ($this->buttons as $btn) {
            $httpValue += $btn->toHttp();
        }

        return $httpValue;
    }

    #[Override]
    public function valid(): bool
    {
        return $this->form->get()?->valid() ?? false;
    }

    #[Override]
    public function failed(): bool
    {
        // Do not use $this->form->get()->failed() because it may be not implemented
        return !$this->valid();
    }

    #[Override]
    public function error(?HttpFieldPath $field = null): FormError
    {
        return $this->form->get()?->error($field) ?? throw new LogicException('Invalid reference');
    }

    #[Override]
    public function container(): ?ChildInterface
    {
        return null; // root cannot have a container
    }

    #[Override]
    public function setContainer(ChildInterface $container): ElementInterface
    {
        throw new BadMethodCallException('Cannot wrap a root element into a container');
    }

    #[Override]
    public function root(): RootElementInterface
    {
        return $this;
    }

    #[Override]
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        $buttons = [];

        foreach ($this->buttons as $button) {
            $buttons[$button->name()] = $button->view($field);
        }

        $view = $this->form->get()?->view($field);
        assert($view !== null);
        $view->setButtons($buttons);

        return $view;
    }

    #[Override]
    public function submitButton(): ?ButtonInterface
    {
        return $this->submitButton;
    }

    #[Override]
    public function button(string $name): ButtonInterface
    {
        if ($btn = $this->buttons[$name] ?? null) {
            return $btn;
        }

        throw new OutOfBoundsException("The button '{$name}' is not found");
    }

    #[Override]
    public function getValidator(): ValidatorInterface
    {
        if ($this->validator === null) {
            $this->validator = new ValidatorBuilder()->getValidator();
        }

        return $this->validator;
    }

    #[Override]
    public function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor ??= PropertyAccess::createPropertyAccessor();
    }

    #[Override]
    public function constraintGroups(): array
    {
        if (!$button = $this->submitButton) {
            return [Constraint::DEFAULT_GROUP];
        }

        return $button->constraintGroups() ?: [Constraint::DEFAULT_GROUP];
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress PossiblyNullReference
     * @psalm-suppress PossiblyNullArrayAccess
     * @psalm-suppress NullableReturnStatement
     */
    #[Override]
    public function offsetGet(mixed $offset): ChildInterface
    {
        return $this->form->get()[$offset];
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->form->get()[$offset]);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        /** @psalm-suppress PossiblyNullReference */
        $this->form->get()[$offset] = $value;
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        if ($form = $this->form->get()) {
            unset($form[$offset]);
        }
    }

    #[Override]
    public function getIterator(): Iterator
    {
        return $this->form->get()?->getIterator() ?? throw new LogicException();
    }

    /**
     * Submit HTTP fields to buttons
     *
     * @param mixed $data The HTTP value
     */
    private function submitToButtons(mixed $data): void
    {
        $this->submitButton = null;

        foreach ($this->buttons as $button) {
            if ($button->submit($data) && $this->submitButton === null) {
                $this->submitButton = $button;
            }
        }
    }
}
