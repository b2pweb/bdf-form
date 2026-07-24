<?php

namespace Tests\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Aggregate\ArrayTransformer;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Registry\Registry;
use Bdf\Form\Struct\StructForm;
use Bdf\Form\Transformer\TransformerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class ArrayTransformerTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[ArrayTransformer(AArrayTransformer::class, ['A'])]
            public ArrayElement $foo;
        };

        $form->submit(['foo' => ['_', '-']]);
        $this->assertEquals(['A_', 'A-'], $form->foo->value());

        $view = $form->view();
        $this->assertEquals(['A_A', 'A-A'], $view['foo']->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestArrayTransformerStruct::class, processor: $processor);

        $form->submit(['foo' => ['_', '-']]);
        $this->assertEquals(['A_', 'A-'], $form['foo']->element()->value());

        $view = $form->view();
        $this->assertEquals(['A_A', 'A-A'], $view['foo']->value());
    }

    #[DataProvider('provideAttributesProcessor')]
    public function test_object(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[ArrayTransformer(new AArrayTransformer('A'))]
            public ArrayElement $foo;
        };

        $form->submit(['foo' => ['_', '-']]);
        $this->assertEquals(['A_', 'A-'], $form->foo->value());

        $view = $form->view();
        $this->assertEquals(['A_A', 'A-A'], $view['foo']->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct_object(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestArrayTransformerObjectStruct::class, processor: $processor);

        $form->submit(['foo' => ['_', '-']]);
        $this->assertEquals(['A_', 'A-'], $form['foo']->element()->value());

        $view = $form->view();
        $this->assertEquals(['A_A', 'A-A'], $view['foo']->value());
    }

    #[DataProvider('provideAttributesProcessor')]
    public function test_from_service(AttributesProcessorInterface $processor)
    {
        $registry = new Registry();
        $registry->registerService(new ServiceArrayTransformer('S'));

        $form = new class(new FormBuilder($registry), $processor) extends AttributeForm {
            #[ArrayTransformer(ServiceArrayTransformer::class)]
            public ArrayElement $foo;
        };

        $form->submit(['foo' => ['_', '-']]);
        $this->assertEquals(['S_', 'S-'], $form->foo->value());

        $view = $form->view();
        $this->assertEquals(['S_S', 'S-S'], $view['foo']->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct_from_service(AttributesProcessorInterface $processor)
    {
        $registry = new Registry();
        $registry->registerService(new ServiceArrayTransformer('S'));

        $form = new StructForm(TestArrayTransformerServiceStruct::class, new FormBuilder($registry), processor: $processor);

        $form->submit(['foo' => ['_', '-']]);
        $this->assertEquals(['S_', 'S-'], $form['foo']->element()->value());

        $view = $form->view();
        $this->assertEquals(['S_S', 'S-S'], $view['foo']->value());
    }

    #[DataProvider('provideAttributesProcessor')]
    public function test_from_service_not_registered(AttributesProcessorInterface $processor)
    {
        $form = new class(new FormBuilder(new Registry()), $processor) extends AttributeForm {
            #[ArrayTransformer(ServiceArrayTransformer::class)]
            public ArrayElement $foo;
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "'.ServiceArrayTransformer::class.'" is not registered.');

        $form->submit(['foo' => ['_', '-']]);
    }

    public function test_object_with_constructor_arguments_throws()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Constructor arguments can be used only with transformer class name');

        new ArrayTransformer(new AArrayTransformer('A'), ['B']);
    }

    public function test_code_generator_object()
    {
        $form = new class extends AttributeForm {
            #[ArrayTransformer(new AArrayTransformer('A'))]
            public ArrayElement $foo;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Tests\Form\Attribute\Aggregate\AArrayTransformer;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', ArrayElement::class);
        $foo->arrayTransformer(new AArrayTransformer(c: 'A'));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
    }
}

PHP
        , $form
);
    }

    public function test_code_generator_from_service()
    {
        $form = new class extends AttributeForm {
            #[ArrayTransformer(ServiceArrayTransformer::class)]
            public ArrayElement $foo;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Tests\Form\Attribute\Aggregate\ServiceArrayTransformer;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', ArrayElement::class);
        $foo->arrayTransformer(ServiceArrayTransformer::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
    }
}

PHP
        , $form
);
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[ArrayTransformer(AArrayTransformer::class, ['A'])]
            public ArrayElement $foo;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Tests\Form\Attribute\Aggregate\AArrayTransformer;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', ArrayElement::class);
        $foo->arrayTransformer(new AArrayTransformer('A'));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
    }
}

PHP
        , $form
);
    }

    public function test_code_generator_struct()
    {
        $this->assertGeneratedStruct(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Aggregate\AArrayTransformer;
use Tests\Form\Attribute\Aggregate\TestArrayTransformerStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestArrayTransformerStruct::class);

        $foo = $builder->add('foo', ArrayElement::class);
        $foo->arrayTransformer(new AArrayTransformer('A'));
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
        , TestArrayTransformerStruct::class,
);
    }
}

class AArrayTransformer implements TransformerInterface
{
    public function __construct(
        public string $c
    ) {
    }

    public function transformToHttp($value, ElementInterface $input)
    {
        return array_map(fn($v) => $v . $this->c, $value);
    }

    public function transformFromHttp($value, ElementInterface $input)
    {
        return array_map(fn($v) => $this->c . $v, $value);
    }
}

class TestArrayTransformerStruct
{
    #[ArrayTransformer(AArrayTransformer::class, ['A'])]
    public array $foo;
}

class TestArrayTransformerObjectStruct
{
    #[ArrayTransformer(new AArrayTransformer('A'))]
    public array $foo;
}

class TestArrayTransformerServiceStruct
{
    #[ArrayTransformer(ServiceArrayTransformer::class)]
    public array $foo;
}

/**
 * Transformer with a required constructor dependency: not instantiable without parameters,
 * so it must be resolved from the registry/container.
 */
class ServiceArrayTransformer implements TransformerInterface
{
    public function __construct(
        public string $prefix
    ) {
    }

    public function transformToHttp($value, ElementInterface $input)
    {
        return array_map(fn($v) => $v . $this->prefix, $value);
    }

    public function transformFromHttp($value, ElementInterface $input)
    {
        return array_map(fn($v) => $this->prefix . $v, $value);
    }
}
