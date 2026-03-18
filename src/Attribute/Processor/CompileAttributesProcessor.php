<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use LogicException;

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
final class CompileAttributesProcessor implements AttributesProcessorInterface
{
    public function __construct(
        /**
         * Resolve the class name of the generated processor class
         * Takes as parameter the form instance, and should return the generated class name
         *
         * The class name must be contained into a namespace
         *
         * @var callable(AttributeForm):non-empty-string
         */
        private $classNameResolver,
        /**
         * Resolve the file name from the generated processor class name
         *
         * @var callable(class-string<AttributesProcessorInterface>):non-empty-string
         */
        private $fileNameResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): PostConfigureInterface
    {
        /** @var class-string<AttributesProcessorInterface&PostConfigureInterface> $className */
        $className = ($this->classNameResolver)($form);

        if (!class_exists($className)) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $this->loadProcessor($className, $form, $builder);
        }

        $generated = new $className();
        $generated->configureBuilder($form, $builder);

        return $generated;
    }

    /**
     * Generate the configurator for the given form
     * Unlike `configureBuilder()` process, the class will be regenerated if already exists,
     * and the class will not be included
     *
     * @param AttributeForm $form Form to generate
     *
     * @return void
     */
    public function generate(AttributeForm $form): void
    {
        /** @var class-string<AttributesProcessorInterface&PostConfigureInterface> $className */
        $className = ($this->classNameResolver)($form);
        $fileName = ($this->fileNameResolver)($className);

        $this->generateProcessor($fileName, $className, $form, new FormBuilder());
    }

    /**
     * Try to load the processor from its file
     *
     * @param class-string<AttributesProcessorInterface&PostConfigureInterface> $className Generated processor class name
     * @param AttributeForm $form Form to build
     * @param FormBuilderInterface $builder Builder to configure
     *
     * @return void
     */
    private function loadProcessor(string $className, AttributeForm $form, FormBuilderInterface $builder): void
    {
        $fileName = ($this->fileNameResolver)($className);

        if (!file_exists($fileName)) {
            $this->generateProcessor($fileName, $className, $form, $builder);
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
     * @param class-string<AttributesProcessorInterface&PostConfigureInterface> $className Generated processor class name
     * @param AttributeForm $form Form to build
     * @param FormBuilderInterface $builder Builder to configure
     *
     * @return void
     */
    private function generateProcessor(string $fileName, string $className, AttributeForm $form, FormBuilderInterface $builder): void
    {
        $generator = new GenerateConfiguratorStrategy($className);
        $processor = new ReflectionProcessor($generator);

        $processor->configureBuilder($form, $builder);

        $code = $generator->code();

        $dirname = dirname($fileName);

        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        file_put_contents($fileName, '<?php' . PHP_EOL . $code);
    }
}
