<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\ElementType;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\Dependencies;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Attribute\Processor\ReflectionProcessor;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Struct\StructForm;
use Bdf\Form\Util\FieldPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class DependenciesTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            public StringElement $foo;
            #[Dependencies('foo', 'bar'), CallbackTransformer(fromHttp: 'bazTransformer')]
            public StringElement $baz;
            public StringElement $bar;

            public function bazTransformer($value)
            {
                return $this->foo->value() . $value . $this->bar->value();
            }
        };

        $form->submit(['foo' => 'a', 'bar' => 'b', 'baz' => 'c']);
        $this->assertSame('acb', $form->baz->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestDependenciesStruct::class, processor: $processor);

        $form->submit(['foo' => 'a', 'bar' => 'b', 'baz' => 'c']);
        $this->assertSame('acb', $form->value()->baz);
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            public StringElement $foo;
            #[Dependencies('foo', 'bar')]
            public StringElement $baz;
            public StringElement $bar;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\StringElement;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', StringElement::class);

        $baz = $builder->add('baz', StringElement::class);
        $baz->depends('foo', 'bar');

        $bar = $builder->add('bar', StringElement::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
        $form->baz = $inner['baz']->element();
        $form->bar = $inner['bar']->element();
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
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Transformer\TransformerInterface;
use Tests\Form\Attribute\Child\TestDependenciesStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestDependenciesStruct::class);

        $foo = $builder->add('foo', StringElement::class);
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));
        $foo->required(null);

        $baz = $builder->add('baz', StringElement::class);
        $baz->depends('foo', 'bar');
        $baz->transformer(new class ($context) implements TransformerInterface {
            /**
             * {@inheritdoc}
             */
            function transformToHttp(mixed $value, ElementInterface $input): mixed
            {
                return $value;
            }

            /**
             * {@inheritdoc}
             */
            function transformFromHttp(mixed $value, ElementInterface $input): mixed
            {
                return $this->context::bazTransformer($value, $input);
            }

            public function __construct(
                private $context,
            ) {
            }
        });
        $baz->hydrator(new Setter(null))->extractor(new Getter(null));
        $baz->required(null);

        $bar = $builder->add('bar', StringElement::class);
        $bar->hydrator(new Setter(null))->extractor(new Getter(null));
        $bar->required(null);

        return null;
    }
}

PHP
            , TestDependenciesStruct::class);
    }
}

class TestDependenciesStruct
{
    public string $foo;
    #[Dependencies('foo', 'bar'), CallbackTransformer(fromHttp: 'bazTransformer')]
    public string $baz;
    public string $bar;

    public static function bazTransformer($value, $input)
    {
        return FieldPath::parse('../foo')->value($input) . $value . FieldPath::parse('../bar')->value($input);
    }
}
