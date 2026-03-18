<?php

namespace Tests\Form\Attribute\Processor\CodeGenerator;

use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use PHPUnit\Framework\Constraint\Count;
use PHPUnit\Framework\TestCase;

class AttributesProcessorGeneratorTest extends TestCase
{
    /**
     *
     */
    public function test_constructor_without_namespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The class name must have a namespace');

        new AttributesProcessorGenerator('MyClass');
    }

    /**
     * @return void
     */
    public function test_empty()
    {
        $generator = new AttributesProcessorGenerator('Generated\Processor');

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;

class Processor implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
    }
}

PHP
        , $generator->print()
);
    }

    /**
     * @return void
     */
    public function test_line()
    {
        $generator = new AttributesProcessorGenerator('Generated\Processor');

        $generator->line('$?->?(...?);', ['foo', 'bar', [1, 2, 3]]);

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;

class Processor implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $foo->bar(1, 2, 3);
    }
}

PHP
        , $generator->print()
);
    }

    /**
     * @return void
     */
    public function test_new()
    {
        $generator = new AttributesProcessorGenerator('Generated\Processor');
        $expression = $generator->new(Count::class, ['min' => 3, 'max' => 6]);

        $this->assertEquals('new Count(min: 3, max: 6)', (string) $expression);

        $generator->line('$builder->satisfy(?);', [$expression]);

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use PHPUnit\Framework\Constraint\Count;

class Processor implements AttributesProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->satisfy(new Count(min: 3, max: 6));
    }
}

PHP
        , $generator->print()
);
    }
}
