<?php

namespace Bdf\Form\Error;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class StringErrorPrinterTest
 */
class StringErrorPrinterTest extends TestCase
{
    /**
     *
     */
    public function test_functional_with_global_error()
    {
        $element = new StringElement(new ConstraintValueValidator([new NotBlank()]));
        $this->assertEquals('This value should not be blank.', $element->submit('')->error()->print(new StringErrorPrinter()));
    }

    /**
     *
     */
    public function test_functional_with_children_error()
    {
        $builder = new FormBuilder();
        $builder->string('foo')->required();
        $builder->integer('bar')->min(5);
        $builder->embedded('embedded', function ($builder) {
            $builder->string('inner')->length(['min' => 5]);
        });

        $element = $builder->buildElement();
        $this->assertEquals(<<<ERROR
foo : This value should not be blank.
bar : This value should be greater than or equal to 5.
embedded : 
  inner : This value is too short. It should have 5 characters or more.
ERROR
, $element->submit([
            'foo' => '',
            'bar' => 2,
            'embedded' => ['inner' => 'a'],
        ])->error()->print(new StringErrorPrinter()));
    }

    /**
     *
     */
    public function test_functional_lineSeparator()
    {
        $builder = new FormBuilder();
        $builder->string('foo')->required();
        $builder->integer('bar')->min(5);
        $builder->embedded('embedded', function ($builder) {
            $builder->string('inner')->length(['min' => 5]);
        });

        $element = $builder->buildElement();
        $this->assertEquals(<<<ERROR
foo : This value should not be blank.;bar : This value should be greater than or equal to 5.;embedded : ;  inner : This value is too short. It should have 5 characters or more.
ERROR
, $element->submit([
            'foo' => '',
            'bar' => 2,
            'embedded' => ['inner' => 'a'],
        ])->error()->print((new StringErrorPrinter())->lineSeparator(';')));
    }

    /**
     *
     */
    public function test_functional_indentString()
    {
        $builder = new FormBuilder();
        $builder->string('foo')->required();
        $builder->integer('bar')->min(5);
        $builder->embedded('embedded', function ($builder) {
            $builder->string('inner')->length(['min' => 5]);
        });

        $element = $builder->buildElement();
        $this->assertEquals(<<<ERROR
foo : This value should not be blank.
bar : This value should be greater than or equal to 5.
embedded : 
**inner : This value is too short. It should have 5 characters or more.
ERROR
            , $element->submit([
                'foo' => '',
                'bar' => 2,
                'embedded' => ['inner' => 'a'],
            ])->error()->print((new StringErrorPrinter())->indentString('**')));
    }

    /**
     *
     */
    public function test_functional_nameSeparator()
    {
        $builder = new FormBuilder();
        $builder->string('foo')->required();
        $builder->integer('bar')->min(5);
        $builder->embedded('embedded', function ($builder) {
            $builder->string('inner')->length(['min' => 5]);
        });

        $element = $builder->buildElement();
        $this->assertEquals(<<<ERROR
foo#This value should not be blank.
bar#This value should be greater than or equal to 5.
embedded#
  inner#This value is too short. It should have 5 characters or more.
ERROR
            , $element->submit([
                'foo' => '',
                'bar' => 2,
                'embedded' => ['inner' => 'a'],
            ])->error()->print((new StringErrorPrinter())->nameSeparator('#')));
    }

    /**
     *
     */
    public function test_functional_maxDepth()
    {
        $builder = new FormBuilder();
        $builder->string('foo')->required();
        $builder->integer('bar')->min(5);
        $builder->embedded('embedded', function ($builder) {
            $builder->string('inner')->length(['min' => 5]);
        });

        $element = $builder->buildElement();
        $this->assertEquals(<<<ERROR
foo : This value should not be blank.
bar : This value should be greater than or equal to 5.
embedded : 
ERROR
            , $element->submit([
                'foo' => '',
                'bar' => 2,
                'embedded' => ['inner' => 'a'],
            ])->error()->print((new StringErrorPrinter())->maxDepth(1)));
    }
}
