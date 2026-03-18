<?php

namespace Tests\Form\Attribute\Element\Date;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Element\Date\DateFormat;
use Bdf\Form\Attribute\Element\Date\Timezone;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\Date\DateTimeElement;
use DateTime;
use Tests\Form\Attribute\TestCase;

class TimezoneTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Timezone('+0500')]
            public DateTimeElement $foo;
        };

        $form->submit(['foo' => '2020-11-02T15:21:00+0200']);

        $this->assertEquals(new DateTime('2020-11-02T18:21:00+0500'), $form->foo->value());
        $this->assertEquals(new \DateTimeZone('+0500'), $form->foo->value()->getTimezone());
        $this->assertEquals(5 * 3600, $form->foo->value()->getOffset());
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Timezone('+0500')]
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
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo = $builder->add('foo', DateTimeElement::class);
        $foo->timezone('+0500');

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
}
