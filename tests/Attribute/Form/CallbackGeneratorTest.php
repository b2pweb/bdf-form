<?php

namespace Tests\Form\Attribute\Form;

use AllowDynamicProperties;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Form\CallbackGenerator;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Struct\StructForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class CallbackGeneratorTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
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

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestCallbackGeneratorStruct::class, processor: $processor);

        $form->submit(['foo' => 'b']);
        $expected = new TestCallbackGeneratorStruct();
        $expected->foo = 'b';
        $expected->bar = 'a';
        $this->assertEquals($expected, $form->value());
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
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
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

    /**
     * @return void
     */
    public function test_code_generator_struct()
    {
        $this->assertGeneratedStruct(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Form\TestCallbackGeneratorStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestCallbackGeneratorStruct::generate(...));

        $foo = $builder->add('foo', StringElement::class);
        $foo->hydrator(new Setter());

        return null;
    }
}

PHP
            , TestCallbackGeneratorStruct::class);
    }
}

#[CallbackGenerator('generate'), AllowDynamicProperties]
class TestCallbackGeneratorStruct
{
    #[Setter]
    public ?string $foo;

    public static function generate(FormInterface $form)
    {
        $o = new self();

        $o->foo = null;
        $o->bar = 'a';

        return $o;
    }
}
