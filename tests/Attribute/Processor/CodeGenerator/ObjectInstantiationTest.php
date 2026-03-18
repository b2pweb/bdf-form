<?php

namespace Tests\Form\Attribute\Processor\CodeGenerator;

use Bdf\Form\Attribute\Processor\CodeGenerator\ClassGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\ObjectInstantiation;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ObjectInstantiationTest extends TestCase
{
    public function test_default()
    {
        $o = new NotBlank();

        $this->assertEquals("new Symfony\Component\Validator\Constraints\NotBlank(['groups' => ['Default']])", ObjectInstantiation::singleArrayParameter($o)->render());

        $this->assertEquals($o, eval('return ' . ObjectInstantiation::singleArrayParameter($o)->render().';'));
    }

    public function test_with_parameters()
    {
        $o = new Range(['min' => 1, 'max' => 10]);

        $this->assertEquals("new Symfony\Component\Validator\Constraints\Range(['min' => 1, 'max' => 10, 'groups' => ['Default']])", ObjectInstantiation::singleArrayParameter($o)->render());

        $this->assertEquals($o, eval('return ' . ObjectInstantiation::singleArrayParameter($o)->render().';'));
    }

    public function test_with_simplified_class_name()
    {
        $o = new Range(['min' => 1, 'max' => 10]);
        $generator = new ClassGenerator(new PhpNamespace('Foo'), new ClassType('Bar'));

        $this->assertEquals("new Range(['min' => 1, 'max' => 10, 'groups' => ['Default']])", ObjectInstantiation::singleArrayParameter($o)->render($generator));
    }
}
