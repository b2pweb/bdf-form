<?php

namespace Bdf\Form\Attribute;

use Attribute;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;
use Override;

use function array_is_list;

/**
 * Call a method on the child/element builder
 * The attribute can be overridden to simplify the declaration of common method calls on builders
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BuilderMethodCall implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The method name to call
         *
         * @var non-empty-string
         */
        public string $method,

        /**
         * Calling arguments.
         * If an associative array is given, named arguments will be used to call the method
         */
        public array $arguments,
    ) {}

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        $builder->{$this->method}(...$this->arguments);
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        if (array_is_list($this->arguments)) {
            $generator->line('$?->?(...?);', [$name, $this->method, $this->arguments]);
        } else {
            $generator->line('$?->?(...?:);', [$name, $this->method, $this->arguments]);
        }
    }
}
