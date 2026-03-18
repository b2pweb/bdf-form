<?php

namespace Tests\Form\Attribute\Element\Date;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\Dependencies;
use Bdf\Form\Attribute\Element\Date\BeforeField;
use Bdf\Form\Attribute\Element\Date\DateFormat;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Tests\Form\Attribute\TestCase;

class BeforeFieldTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Dependencies('bar'), BeforeField('bar')]
            public DateTimeElement $foo;

            public DateTimeElement $bar;
        };

        $form->submit([
            'foo' => '2020-11-02T15:21:00Z',
            'bar' => '2020-11-02T15:23:00Z',
        ]);

        $this->assertTrue($form->valid());

        $form->submit([
            'foo' => '2020-11-02T15:21:00Z',
            'bar' => '2020-11-02T15:20:50Z',
        ]);
        $this->assertFalse($form->valid());
        $this->assertEquals([
            'foo' => 'This value should be less than Nov 2, 2020, 3:20 PM.',
        ], self::normalizeSpace($form->error()->toArray()));
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_with_message(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Dependencies('bar'), BeforeField('bar', message: 'my error')]
            public DateTimeElement $foo;

            public DateTimeElement $bar;
        };

        $form->submit([
            'foo' => '2020-11-02T15:21:00Z',
            'bar' => '2020-11-02T15:23:00Z',
        ]);

        $this->assertTrue($form->valid());

        $form->submit([
            'foo' => '2020-11-02T15:21:00Z',
            'bar' => '2020-11-02T15:20:50Z',
        ]);
        $this->assertFalse($form->valid());
        $this->assertEquals([
            'foo' => 'my error',
        ], $form->error()->toArray());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_with_or_equal(AttributesProcessorInterface $processor)
    {
        $form = new class(null, $processor) extends AttributeForm {
            #[Dependencies('bar'), BeforeField('bar', orEqual: true)]
            public DateTimeElement $foo;

            public DateTimeElement $bar;
        };

        $form->submit([
            'foo' => '2020-11-02T15:21:00Z',
            'bar' => '2020-11-02T15:21:00Z',
        ]);

        $this->assertTrue($form->valid());

        $form->submit([
            'foo' => '2020-11-02T15:21:00Z',
            'bar' => '2020-11-02T15:20:50Z',
        ]);
        $this->assertFalse($form->valid());
        $this->assertEquals([
            'foo' => 'This value should be less than or equal to Nov 2, 2020, 3:20 PM.',
        ], self::normalizeSpace($form->error()->toArray()));
    }

    public function test_code_generator()
    {
        $form = new class extends AttributeForm {
            #[Dependencies('bar'), BeforeField('bar', 'my error', true)]
            public DateTimeElement $foo;
            public DateTimeElement $bar;
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
        $foo->depends('bar');
        $foo->beforeField('bar', 'my error', true);

        $bar = $builder->add('bar', DateTimeElement::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        $form->foo = $inner['foo']->element();
        $form->bar = $inner['bar']->element();
    }
}

PHP
            , $form
        );
    }

    public static function normalizeSpace(array|string $value): array|string
    {
        if (\is_array($value)) {
            return \array_map([self::class, 'normalizeSpace'], $value);
        }

        return \preg_replace('/\p{Zs}+/u', ' ', $value);
    }
}
