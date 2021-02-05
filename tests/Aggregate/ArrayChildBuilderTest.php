<?php

namespace Bdf\Form\Aggregate;

use PHPUnit\Framework\TestCase;

class ArrayChildBuilderTest extends TestCase
{
    /**
     *
     */
    public function test_filterEmptyValues()
    {
        $builder = new FormBuilder();
        $builder->array('arr')->form(function ($builder) {
            $builder->string('foo')->required()->setter();
        })->setter();
        $form = $builder->buildElement();

        $form->submit([
            'arr' => [
                null,
                [],
                ['foo' => ''],
                '',
                ['foo' => 'bar'],
            ]
        ]);

        $this->assertEquals(['arr' => [2 => ['foo' => 'This value should not be blank.']]], $form->error()->toArray());
        $this->assertSame([
            'arr' => [
                2 => ['foo' => ''],
                4 => ['foo' => 'bar'],
            ]
        ], $form->value());

        $builder = new FormBuilder();
        $builder->array('arr')
            ->filterEmptyValues(false)
            ->form(function ($builder) { $builder->string('foo')->required()->setter(); })
            ->setter()
        ;
        $form = $builder->buildElement();

        $form->submit([
            'arr' => [
                null,
                [],
                ['foo' => ''],
                '',
                ['foo' => 'bar'],
            ]
        ]);

        $this->assertEquals(['arr' => [
            0 => ['foo' => 'This value should not be blank.'],
            1 => ['foo' => 'This value should not be blank.'],
            2 => ['foo' => 'This value should not be blank.'],
            3 => ['foo' => 'This value should not be blank.'],
        ]], $form->error()->toArray());

        $this->assertSame([
            'arr' => [
                0 => ['foo' => null],
                1 => ['foo' => null],
                2 => ['foo' => ''],
                3 => ['foo' => null],
                4 => ['foo' => 'bar'],
            ]
        ], $form->value());
    }
}
