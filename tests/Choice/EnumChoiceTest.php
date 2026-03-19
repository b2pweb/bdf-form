<?php

namespace Bdf\Form\Choice;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class EnumChoiceTest extends TestCase
{
    /**
     *
     */
    public function test_without_label_closure()
    {
        $choice = new EnumChoice(MyStringEnum::class);

        $this->assertSame([
            'Foo' => 'value',
            'Bar' => 'other',
        ], $choice->values());

        $view = $choice->view();

        $this->assertEquals([
            new ChoiceView('value', 'Foo'),
            new ChoiceView('other', 'Bar'),
        ], $view);
    }

    /**
     *
     */
    public function test_with_label_closure()
    {
        $choice = new EnumChoice(
            MyStringEnum::class,
            static fn (MyStringEnum $enum) => $enum->name . ' (' . $enum->value . ')',
        );

        $this->assertSame([
            'Foo (value)' => 'value',
            'Bar (other)' => 'other',
        ], $choice->values());

        $view = $choice->view();

        $this->assertEquals([
            new ChoiceView('value', 'Foo (value)'),
            new ChoiceView('other', 'Bar (other)'),
        ], $view);
    }

    /**
     *
     */
    public function test_view_with_configurator()
    {
        $choice = new EnumChoice(MyStringEnum::class);

        $view = $choice->view(
            static function (ChoiceView $view) {
                $view->setSelected($view->value === 'other');
            },
        );

        $expected = [
            new ChoiceView('value', 'Foo'),
            new ChoiceView('other', 'Bar'),
        ];
        $expected[1]->setSelected(true);

        $this->assertEquals($expected, $view);
    }

}

enum MyStringEnum: string
{
    case Foo = 'value';
    case Bar = 'other';
}

