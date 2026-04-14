<?php

namespace Tests\Form\Attribute\Aggregate;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Attribute\Aggregate\ArrayTransformer;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\ElementInterface;
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
