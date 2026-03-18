<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Choice\ArrayChoice;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Positive;

use function strtolower;

class EnumElementBuilderTest extends TestCase
{
    /**
     * @var EnumElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new EnumElementBuilder();
    }

    /**
     *
     */
    public function test_buildElement_backed()
    {
        $element = $this->builder->enumClass(Fixtures\TestBackedEnum::class)->buildElement();
        $this->assertInstanceOf(BackedEnumElement::class, $element);
    }

    /**
     *
     */
    public function test_buildElement_unit()
    {
        $element = $this->builder->enumClass(Fixtures\TestUnitEnum::class)->buildElement();
        $this->assertInstanceOf(UnitEnumElement::class, $element);
    }

    /**
     *
     */
    public function test_buildElement_backed_false_with_backed_enum()
    {
        $element = $this->builder->enumClass(Fixtures\TestBackedEnum::class)->backed(false)->buildElement();
        $this->assertInstanceOf(UnitEnumElement::class, $element);
    }

    /**
    /**
     *
     */
    public function test_buildElement_backed_true_with_unit_enum_should_fail()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The enum class "Bdf\Form\Leaf\Fixtures\TestUnitEnum" is not a backed enum');

        $this->builder->enumClass(Fixtures\TestUnitEnum::class)->backed(true)->buildElement();
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->enumClass(Fixtures\TestBackedEnum::class)->satisfy(new NotEqualTo(Fixtures\TestBackedEnum::Foo))->buildElement();

        $this->assertFalse($element->submit('foo')->valid());
        $this->assertTrue($element->submit('bar')->valid());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = $this->builder
            ->enumClass(Fixtures\TestBackedEnum::class)
            ->transformer(static fn ($value) => strtolower($value))
            ->buildElement()
        ;

        $this->assertEquals(Fixtures\TestBackedEnum::Foo, $element->submit('FOO')->value());
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->builder->enumClass(Fixtures\TestBackedEnum::class)->value(Fixtures\TestBackedEnum::Foo)->buildElement();

        $this->assertSame(Fixtures\TestBackedEnum::Foo, $element->value());
    }

    /**
     *
     */
    public function test_required()
    {
        $element = $this->builder->enumClass(Fixtures\TestBackedEnum::class)->required()->buildElement();

        $element->submit(null);
        $this->assertEquals('This value should not be blank.', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $element = $this->builder->enumClass(Fixtures\TestBackedEnum::class)->required('my message')->buildElement();

        $element->submit(null);
        $this->assertEquals('my message', $element->error()->global());
    }

    /**
     *
     */
    public function test_choices()
    {
        $element = $this->builder->enumClass(Fixtures\TestBackedEnum::class)->choices([Fixtures\TestBackedEnum::Bar])->buildElement();

        $this->assertEquals(new ArrayChoice([Fixtures\TestBackedEnum::Bar]), $element->choices());

        $element->submit('foo');
        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('The value you selected is not a valid choice.', $element->error()->global());
    }

    /**
     *
     */
    public function test_choices_custom_message()
    {
        $element = $this->builder->enumClass(Fixtures\TestBackedEnum::class)->choices([Fixtures\TestBackedEnum::Bar], 'my error')->buildElement();

        $element->submit('foo');
        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('my error', $element->error()->global());
    }
}
