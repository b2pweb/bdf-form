<?php

namespace Bdf\Form\Error;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Validator\ConstraintValueValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ImplodeErrorPrinterTest
 */
class ImplodeErrorPrinterTest extends TestCase
{
    /**
     *
     */
    public function test_functional_with_global_error()
    {
        $element = new StringElement(new ConstraintValueValidator(new NotBlank()));
        $this->assertEquals('This value should not be blank.', $element->submit('')->error()->print(new ImplodeErrorPrinter()));
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
        $this->assertEquals("This value should not be blank.\nThis value should be greater than or equal to 5.\nThis value is too short. It should have 5 characters or more.", $element->submit([
            'foo' => '',
            'bar' => 2,
            'embedded' => ['inner' => 'a'],
        ])->error()->print(new ImplodeErrorPrinter()));
    }

    /**
     *
     */
    public function test_functional_with_custom_separator()
    {
        $builder = new FormBuilder();
        $builder->string('foo')->required();
        $builder->integer('bar')->min(5);
        $builder->embedded('embedded', function ($builder) {
            $builder->string('inner')->length(['min' => 5]);
        });

        $element = $builder->buildElement();
        $this->assertEquals("This value should not be blank.<br />This value should be greater than or equal to 5.<br />This value is too short. It should have 5 characters or more.", $element->submit([
            'foo' => '',
            'bar' => 2,
            'embedded' => ['inner' => 'a'],
        ])->error()->print(new ImplodeErrorPrinter('<br />')));
    }
}
