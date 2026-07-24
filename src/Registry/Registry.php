<?php

namespace Bdf\Form\Registry;

use Bdf\Form\Aggregate\Form;
use Bdf\Form\Custom\CustomForm;
use Bdf\Form\Custom\CustomFormBuilder;
use Bdf\Form\Struct\StructForm;
use Bdf\Form\Struct\StructFormBuilder;
use InvalidArgumentException;
use Override;

use function sprintf;

/**
 * Base registry interface
 */
final class Registry extends AbstractRegistry
{
    /**
     * @var array<class-string, object>
     * @psalm-var class-string-map<T, T>
     */
    private array $services = [];

    /**
     * Registry constructor.
     */
    public function __construct()
    {
        $this->register(CustomForm::class, function (RegistryInterface $registry, string $formClass) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return new CustomFormBuilder($formClass, $this->elementBuilder(Form::class));
        });

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->register(StructForm::class, fn (RegistryInterface $registry) => new StructFormBuilder(builder: $registry->elementBuilder(Form::class)));
    }

    #[Override]
    public function service(string $class): object
    {
        if (!isset($this->services[$class])) {
            throw new InvalidArgumentException(sprintf('Service "%s" is not registered.', $class));
        }

        return $this->services[$class];
    }

    /**
     * Register a new service, associated to the given type
     *
     * @param T $service
     * @param class-string<T>|null $class The registered class name. If not set, the serice class name will be used
     *
     * @return void
     * @template T as object
     */
    public function registerService(object $service, ?string $class = null): void
    {
        $class ??= $service::class;

        $this->services[$class] = $service;
    }
}
