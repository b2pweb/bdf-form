<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Closure;
use LogicException;
use Override;

use function is_string;

/**
 * Processor for compile attributes to native PHP code for build the form
 *
 * - Resolve the class name of the generated processor
 * - If the class do not exist, resolve its file name
 * - If the file do not exist, generate the processor class
 * - Include the processor class file
 * - Instantiate the generated processor
 * - Delegate the form configuration to the generated processor
 *
 * @api
 */
final readonly class CompileAttributesProcessor implements AttributesProcessorInterface
{
    public function __construct(
        /**
         * Resolve the class name of the generated processor class
         * Takes as parameter the form instance, and should return the generated class name
         *
         * The class name must be contained into a namespace
         *
         * @var callable(class-string):non-empty-string
         */
        private mixed $classNameResolver,
        /**
         * Resolve the file name from the generated processor class name
         *
         * @var callable(class-string<AttributesProcessorInterface>):non-empty-string
         */
        private mixed $fileNameResolver,

        /**
         * Factory for the inner processor used to generate the code, if the generated class do not exist yet
         *
         * @var (Closure(ReflectionStrategyInterface):AttributesProcessorInterface)|null
         */
        private ?Closure $innerProcessorFactory = null
    ) {}

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress PossiblyUnusedReturnValue
     */
     #[Override]
    public function configureBuilder(string|object $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $formClassName = is_string($context) ? $context : $context::class;

        /** @var class-string<AttributesProcessorInterface> $className */
        $className = ($this->classNameResolver)($formClassName);

        if (!class_exists($className)) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $this->loadProcessor($className, $context, $builder);
        }

        $generated = new $className();
        $generated->configureBuilder($context, $builder);

        return $generated instanceof PostConfigureInterface ? $generated : null;
    }

    /**
     * Generate the configurator for the given form
     * Unlike `configureBuilder()` process, the class will be regenerated if already exists,
     * and the class will not be included
     *
     * @param class-string|object $context Form to generate
     *
     * @return void
     */
    public function generate(string|object $context): void
    {
        /** @var class-string<AttributesProcessorInterface&PostConfigureInterface> $className */
        $className = ($this->classNameResolver)(is_string($context) ? $context : $context::class);
        $fileName = ($this->fileNameResolver)($className);

        $this->generateProcessor($fileName, $className, $context, new FormBuilder());
    }

    /**
     * Try to load the processor from its file
     *
     * @param class-string<AttributesProcessorInterface> $className Generated processor class name
     * @param class-string|object $context Form to build
     * @param FormBuilderInterface $builder Builder to configure
     *
     * @return void
     */
    private function loadProcessor(string $className, string|object $context, FormBuilderInterface $builder): void
    {
        $fileName = ($this->fileNameResolver)($className);

        if (!file_exists($fileName)) {
            $this->generateProcessor($fileName, $className, $context, $builder);
        }

        require_once $fileName;

        if (!class_exists($className) || !is_subclass_of($className, AttributesProcessorInterface::class)) {
            throw new LogicException('Invalid generated class "' . $className . '" in file "' . $fileName . '"');
        }
    }

    /**
     * Generate the processor class and save it into the given file
     *
     * @param string $fileName Target file
     * @param class-string<AttributesProcessorInterface> $className Generated processor class name
     * @param class-string|object $context Form to build
     * @param FormBuilderInterface $builder Builder to configure
     *
     * @return void
     */
    private function generateProcessor(string $fileName, string $className, string|object $context, FormBuilderInterface $builder): void
    {
        $generator = new GenerateConfiguratorStrategy($className);
        $processor = $this->innerProcessorFactory
            ? ($this->innerProcessorFactory)($generator)
            : new ReflectionProcessor($generator)
        ;

        $processor->configureBuilder($context, $builder);

        $code = $generator->code();

        $dirname = dirname($fileName);

        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        file_put_contents($fileName, '<?php' . PHP_EOL . $code);
    }
}
