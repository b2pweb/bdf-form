<?php

namespace Tests\Form\Attribute;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\CompileAttributesProcessor;
use Bdf\Form\Attribute\Processor\ConfigureFormBuilderStrategy;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return AttributesProcessorInterface[]
     */
    public function provideAttributesProcessor(): array
    {
        return [
            'reflection' => [new ReflectionProcessor(new ConfigureFormBuilderStrategy())],
            'compile' => [new CompileAttributesProcessor(
                fn ($form) => 'Generated\\G' . bin2hex(random_bytes(16)),
                fn ($className) => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Generated_' . str_replace('\\', '_', $className) . '.php'
            )],
        ];
    }

    public function assertGenerated(string $expected, AttributeForm $form): void
    {
        $generator = new GenerateConfiguratorStrategy('Generated\GeneratedConfigurator');
        $processor = new ReflectionProcessor($generator);

        $processor->configureBuilder($form, new FormBuilder());
        $this->assertEquals($expected, $generator->code());
    }
}
