<?php

namespace Tests\Form\Attribute\Element\Date;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\Date\DateTimeClass;
use Bdf\Form\Attribute\Element\Raw;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Struct\StructForm;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class DateTimeClassTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[DateTimeClass(MyCustomDate::class)]
            public DateTimeElement $foo;
        };

        $form->submit(['foo' => '2020-11-02T15:21:31+0100']);

        $this->assertEquals(new MyCustomDate('2020-11-02T15:21:31'), $form->foo->value());
        $this->assertInstanceOf(MyCustomDate::class, $form->foo->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestDateTimeStruct::class, processor: $processor);

        $form->submit(['foo' => '2020-11-02T15:21:31+0100']);

        $this->assertEquals(new MyCustomDate('2020-11-02T15:21:31'), $form->value()->foo);
        $this->assertInstanceOf(MyCustomDate::class, $form->value()->foo);
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[DateTimeClass(MyCustomDate::class)]
            public DateTimeElement $foo;
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Tests\Form\Attribute\Element\Date\MyCustomDate;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', DateTimeElement::class);
        $foo->className(MyCustomDate::class);

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

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Tests\Form\Attribute\Element\Date\MyCustomDate;
use Tests\Form\Attribute\Element\Date\TestDateTimeStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestDateTimeStruct::class);

        $foo = $builder->add('foo', DateTimeElement::class);
        $foo->className(MyCustomDate::class);
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
            , TestDateTimeStruct::class
        );
    }
}

class MyCustomDate extends \DateTime
{
}

class TestDateTimeStruct
{
    #[DateTimeClass(MyCustomDate::class)]
    public ?DateTime $foo;
}
