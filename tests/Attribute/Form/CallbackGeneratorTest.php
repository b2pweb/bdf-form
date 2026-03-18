<?php

namespace Tests\Form\Attribute\Form;

use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Form\CallbackGenerator;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\TestCase;

class CallbackGeneratorTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new #[CallbackGenerator('generate')] class extends AttributeForm {
            #[Setter]
            public StringElement $foo;

            public function generate(FormInterface $form)
            {
                return (object) ['foo' => null, 'bar' => 'a'];
            }
        };

        $form->submit(['foo' => 'b']);
        $this->assertEquals((object) ['foo' => 'b', 'bar' => 'a'], $form->value());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new #[CallbackGenerator('generate')] class extends AttributeForm {
            public function generate(FormInterface $form)
            {
                return (object) ['foo' => null, 'bar' => 'a'];
            }
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates([$this, 'generate']);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
    }
}

PHP
            , $form);
    }
}
