<?php

namespace Bdf\Form\Struct;

use Bdf\Form\Struct\Fixtures\OptionalDto;
use Bdf\Form\Struct\Fixtures\SimpleDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

class StructFormBuilderTest extends TestCase
{
    private StructFormBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new StructFormBuilder();
    }

    #[Test]
    public function simple()
    {
        $form = $this->builder->class(SimpleDto::class)->buildElement();

        $this->assertInstanceOf(StructForm::class, $form);
        $this->assertEquals(new SimpleDto('foo', 42), $form->submit(['name' => 'foo', 'value' => 42])->value());
    }

    #[Test]
    public function missingClass()
    {
        $this->expectException(\LogicException::class);
        $this->builder->buildElement();
    }

    #[Test]
    public function cannotResetClass()
    {
        $this->expectException(\LogicException::class);
        $this->builder->class(SimpleDto::class)->class(OptionalDto::class);
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $this->builder->class(OptionalDto::class);
        $form = $this->builder->satisfy(function ($value, $form) {
            if ($form['name']->element()->value() == 'bar') {
                return 'error';
            }
        })->buildElement();

        $this->assertEquals('error', $form->submit(['name' => 'bar'])->error()->global());
        $this->assertTrue($form->submit(['name' => 'baz'])->valid());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $this->builder->class(OptionalDto::class);

        $form = $this->builder->transformer(function (array $value) {
            return array_map(strtoupper(...), $value);
        })->buildElement();

        $this->assertEquals(new OptionalDto('FOO'), $form->submit(['name' => 'foo'])->value());
    }
}
