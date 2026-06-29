<?php

namespace Bdf\Form\Struct;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Util\DelegateElementBuilderTrait;
use LogicException;
use Override;

/**
 * @implements ElementBuilderInterface<StructForm<object>>
 */
final class StructFormBuilder implements ElementBuilderInterface
{
    use DelegateElementBuilderTrait;

    /**
     * The struct class name
     *
     * @var class-string|null
     */
    private ?string $class = null;

    private readonly FormBuilderInterface $builder;

    public function __construct(
        private readonly ?AttributesProcessorInterface $processor = null,
        ?FormBuilderInterface $builder = null
    )
    {
        $this->builder = $builder ?? new FormBuilder();
    }

    /**
     * Define the struct class name
     *
     * @param class-string $class
     * @return $this
     * @internal
     */
    public function class(string $class): self
    {
        if ($this->class !== null) {
            throw new LogicException('The class has already been set.');
        }

        $this->class = $class;
        return $this;
    }

    #[Override]
    protected function getElementBuilder(): ElementBuilderInterface
    {
        return $this->builder;
    }

    #[Override]
    public function buildElement(): StructForm
    {
        if ($this->class === null) {
            throw new LogicException('A struct class is required to build a StructForm');
        }

        return new StructForm(
            $this->class,
            $this->builder,
            $this->processor,
        );
    }
}
