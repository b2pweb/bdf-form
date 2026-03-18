<?php

namespace Tests\Form\Attribute;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\AsTransformer;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\Button\SubmitButton;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Filter\FilterVar;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Transformer\TransformerInterface;
use Tests\Form\Attribute\TestCase;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class FunctionalTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_simple(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[NotBlank, Length(min: 3), Getter, Setter]
            public StringElement $firstName;

            #[NotBlank, Length(min: 3), Getter, Setter]
            public StringElement $lastName;

            #[Getter, Setter, GreaterThan(0)]
            public IntegerElement $age;
        };

        $this->assertInstanceOf(ChildInterface::class, $form['firstName']);
        $this->assertInstanceOf(ChildInterface::class, $form['lastName']);
        $this->assertInstanceOf(ChildInterface::class, $form['age']);

        $this->assertInstanceOf(StringElement::class, $form['firstName']->element());
        $this->assertInstanceOf(StringElement::class, $form['lastName']->element());
        $this->assertInstanceOf(IntegerElement::class, $form['age']->element());

        $this->assertSame($form['firstName']->element(), $form->firstName);
        $this->assertSame($form['lastName']->element(), $form->lastName);
        $this->assertSame($form['age']->element(), $form->age);

        $form->submit(['firstName' => 'John', 'lastName' => 'Doe', 'age' => '35']);
        $this->assertTrue($form->valid());

        $this->assertSame(['firstName' => 'John', 'lastName' => 'Doe', 'age' => 35], $form->value());

        $form->submit(['firstName' => 'Foo', 'lastName' => 'B', 'age' => '-5']);

        $this->assertFalse($form->valid());
        $this->assertEquals([
            'lastName' => 'This value is too short. It should have 3 characters or more.',
            'age' => 'This value should be greater than 0.',
        ], $form->error()->toArray());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_setter_with_name(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Setter('bar')]
            public StringElement $foo;
        };

        $form->submit(['foo' => 'azerty']);
        $this->assertSame(['bar' => 'azerty'], $form->value());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_getter_with_name(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Getter('bar')]
            public StringElement $foo;
        };

        $form->import(['bar' => 'aqw']);
        $this->assertSame('aqw', $form->foo->value());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_inheritance(AttributesProcessorInterface $processor)
    {
        $form = new ChildForm(null, $processor);

        $this->assertInstanceOf(StringElement::class, $form['foo']->element());
        $this->assertInstanceOf(IntegerElement::class, $form['bar']->element());

        $form->submit([]);

        $this->assertFalse($form->valid());
        $this->assertEquals(['foo' => 'This value should not be blank.', 'bar' => 'This value should not be blank.'], $form->error()->toArray());

        $form->submit(['foo' => 'azerty', 'bar' => '25']);
        $this->assertTrue($form->valid());

        $this->assertSame(['bar' => 25, 'foo' => 'azerty'], $form->value());
    }

    /**
     *
     */
    public function test_inheritance_code_generator()
    {
        $form = new ChildForm();

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
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Tests\Form\Attribute\BaseForm;
use Tests\Form\Attribute\ChildForm;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $bar = $builder->add('bar', IntegerElement::class);
        $bar->satisfy(new NotBlank());
        $bar->extractor(new Getter());
        $bar->hydrator(new Setter());
        $bar->satisfy(new GreaterThan(5));

        $foo = $builder->add('foo', StringElement::class);
        $foo->satisfy(new NotBlank());
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        (\Closure::bind(function () use ($inner, $form) {
            $form->bar = $inner['bar']->element();
        }, null, ChildForm::class))();
        (\Closure::bind(function () use ($inner, $form) {
            $form->foo = $inner['foo']->element();
        }, null, BaseForm::class))();
    }
}

PHP
        , $form
);
    }

    /**
     *
     */
    public function test_inheritance_code_generator_with_method()
    {
        $form = new ChildFormWithMethod();

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
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Tests\Form\Attribute\BaseFormWithMethod;
use Tests\Form\Attribute\ChildFormWithMethod;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $bar = $builder->add('bar', IntegerElement::class);
        $bar->satisfy(new NotBlank());
        $bar->extractor(new Getter());
        $bar->hydrator(new Setter());
        $bar->satisfy(new GreaterThan(5));

        $foo = $builder->add('foo', StringElement::class);
        $foo->extractor(new Getter());
        $foo->hydrator(new Setter());
        $foo->transformer([$form, 'transform']);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        (\Closure::bind(function () use ($inner, $form) {
            $form->bar = $inner['bar']->element();
        }, null, ChildFormWithMethod::class))();
        (\Closure::bind(function () use ($inner, $form) {
            $form->foo = $inner['foo']->element();
        }, null, BaseFormWithMethod::class))();
    }
}

PHP
            , $form
        );
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_buttons(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            public ButtonInterface $foo;
            public ButtonInterface $bar;
        };

        $form->submit([]);

        $this->assertInstanceOf(SubmitButton::class, $form->foo);
        $this->assertInstanceOf(SubmitButton::class, $form->bar);

        $this->assertFalse($form->foo->clicked());
        $this->assertFalse($form->bar->clicked());

        $form->submit(['foo' => 'ok']);

        $this->assertTrue($form->foo->clicked());
        $this->assertFalse($form->bar->clicked());

        $form->submit(['bar' => 'ok']);

        $this->assertFalse($form->foo->clicked());
        $this->assertTrue($form->bar->clicked());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_filter(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[FilterVar(FILTER_SANITIZE_FULL_SPECIAL_CHARS), Setter]
            public StringElement $foo;
        };

        $form->submit(['foo' => '<hello>&world']);
        $this->assertSame(['foo' => '&lt;hello&gt;&amp;world'], $form->value());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_transformer(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[MyTransformer, Setter]
            public StringElement $foo;
        };

        $form->submit(['foo' => 'aaa']);
        $this->assertSame(['foo' => 'YWFh'], $form->value());
    }
}

class BaseForm extends AttributeForm
{
    #[NotBlank, Getter, Setter]
    private StringElement $foo;
}

class ChildForm extends BaseForm
{
    #[NotBlank, Getter, Setter, GreaterThan(5)]
    private IntegerElement $bar;
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class MyTransformer implements TransformerInterface
{
    public function transformToHttp($value, ElementInterface $input)
    {
        return base64_decode($value);
    }

    public function transformFromHttp($value, ElementInterface $input)
    {
        return base64_encode($value);
    }
}

class BaseFormWithMethod extends AttributeForm
{
    #[Getter, Setter]
    private StringElement $foo;

    #[AsTransformer('foo')]
    public function transform(string $value): string
    {
        return str_rot13($value);
    }
}

class ChildFormWithMethod extends BaseFormWithMethod
{
    #[NotBlank, Getter, Setter, GreaterThan(5)]
    private IntegerElement $bar;
}
