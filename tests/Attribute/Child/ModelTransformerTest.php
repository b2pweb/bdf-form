<?php

namespace Tests\Form\Attribute\Child;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\GetSet;
use Bdf\Form\Attribute\Child\ModelTransformer;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Transformer\TransformerInterface;
use Tests\Form\Attribute\TestCase;

class ModelTransformerTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new #[Generates(Struct::class)] class extends AttributeForm {
            #[ModelTransformer(ATransformer::class), Getter, Setter]
            public StringElement $a;
            #[ModelTransformer(BTransformer::class), Getter, Setter]
            public IntegerElement $b;

            #[ModelTransformer(TransformerWithArguments::class, ['foo_']), GetSet]
            public StringElement $c;
        };

        $form->submit(['a' => 'foo', 'b' => '15']);
        $this->assertEquals(new Struct(a: 'Zm9v', b: 'f'), $form->value());

        $form->import(new Struct(a: 'SGVsbG8gV29ybGQgIQ==', b: 'a'));
        $this->assertEquals('Hello World !', $form->a->value());
        $this->assertEquals(10, $form->b->value());

        $form->submit(['c' => 'bar']);
        $this->assertEquals('foo_bar', $form->value()->c);

        $form->import(new Struct(c: 'foo_abc'));
        $this->assertEquals('abc', $form->c->value());
    }

    public function test_code_generator()
    {
        $form = new #[Generates(Struct::class)] class extends AttributeForm {
            #[ModelTransformer(ATransformer::class), Getter, Setter]
            public StringElement $a;
            #[ModelTransformer(BTransformer::class), Getter, Setter]
            public IntegerElement $b;

            #[ModelTransformer(TransformerWithArguments::class, ['foo_']), GetSet]
            public StringElement $c;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Child\ATransformer;
use Tests\Form\Attribute\Child\BTransformer;
use Tests\Form\Attribute\Child\Struct;
use Tests\Form\Attribute\Child\TransformerWithArguments;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(Struct::class);

        $a = $builder->add('a', StringElement::class);
        $a->modelTransformer(new ATransformer());
        $a->extractor(new Getter());
        $a->hydrator(new Setter());

        $b = $builder->add('b', IntegerElement::class);
        $b->modelTransformer(new BTransformer());
        $b->extractor(new Getter());
        $b->hydrator(new Setter());

        $c = $builder->add('c', StringElement::class);
        $c->modelTransformer(new TransformerWithArguments('foo_'));
        $c->hydrator(new Setter(null))->extractor(new Getter(null));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->a = $inner['a']->element();
        $form->b = $inner['b']->element();
        $form->c = $inner['c']->element();
    }
}

PHP
        , $form
);
    }
}

class Struct
{
    public function __construct(
        public ?string $a = null,
        public ?string $b = null,
        public ?string $c = 'foo_',
    ) {}
}

class ATransformer implements TransformerInterface
{
    public function transformToHttp($value, ElementInterface $input)
    {
        return base64_decode((string) $value);
    }

    public function transformFromHttp($value, ElementInterface $input)
    {
        return base64_encode((string) $value);
    }
}

class BTransformer implements TransformerInterface
{
    public function transformToHttp($value, ElementInterface $input)
    {
        return $value ? hexdec($value) : 0;
    }

    public function transformFromHttp($value, ElementInterface $input)
    {
        return $value ? dechex($value) : '';
    }
}

class TransformerWithArguments implements TransformerInterface
{
    public function __construct(public string $prefix) {}

    public function transformToHttp($value, ElementInterface $input)
    {
        return substr($value, strlen((string) $this->prefix));
    }

    public function transformFromHttp($value, ElementInterface $input)
    {
        return $this->prefix . $value;
    }
}
