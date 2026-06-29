<?php

namespace Tests\Form\Attribute\Element\Date;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\Date\DateFormat;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Struct\StructForm;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Form\Attribute\TestCase;

class DateFormatTest extends TestCase
{
    #[DataProvider('provideAttributesProcessor')]
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[DateFormat('d/m/Y H:i')]
            public DateTimeElement $foo;
        };

        $form->submit(['foo' => '02/11/2020 15:21']);

        $this->assertEquals(new MyCustomDate('2020-11-02T15:21:00'), $form->foo->value());
    }

    #[Test, DataProvider('provideStructAttributesProcessor')]
    public function struct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(TestDateFormatStruct::class, processor: $processor);

        $form->submit(['foo' => '02/11/2020 15:21']);

        $this->assertEquals(new DateTime('2020-11-02T15:21:00'), $form->value()->foo);
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[DateFormat('d/m/Y H:i')]
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

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', DateTimeElement::class);
        $foo->format('d/m/Y H:i');

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
use Tests\Form\Attribute\Element\Date\TestDateFormatStruct;

class GeneratedConfigurator implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(TestDateFormatStruct::class);

        $foo = $builder->add('foo', DateTimeElement::class);
        $foo->format('d/m/Y H:i');
        $foo->hydrator(new Setter(null))->extractor(new Getter(null));

        return null;
    }
}

PHP
            , TestDateFormatStruct::class
        );
    }
}
class TestDateFormatStruct
{
    #[DateFormat('d/m/Y H:i')]
    public ?DateTime $foo;
}
