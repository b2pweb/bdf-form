<?php

namespace Bdf\Form\PropertyAccess;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Leaf\StringElementBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class GetterTest
 */
class GetterTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $builder = new ChildBuilder('public', new StringElementBuilder());
        $builder->extractor(new Getter());

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $input->import(new GetterTestEntity('my value'));

        $this->assertSame('my value', $input->element()->value());
    }

    /**
     *
     */
    public function test_custom_property_name()
    {
        $builder = new ChildBuilder('child', new StringElementBuilder());
        $builder->extractor(new Getter('public'));

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $input->import(new GetterTestEntity('my value'));

        $this->assertSame('my value', $input->element()->value());
    }

    /**
     *
     */
    public function test_with_transformer()
    {
        $builder = new ChildBuilder('public', new StringElementBuilder());
        $builder->extractor(new Getter(function ($value) use(&$args) {
            $args = func_get_args();

            return strtoupper($value);
        }));

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $input->import(new GetterTestEntity('my value'));

        $this->assertSame('MY VALUE', $input->element()->value());
        $this->assertCount(2, $args);
        $this->assertSame($input, $args[1]);
    }

    /**
     *
     */
    public function test_with_property_name_and_transformer()
    {
        $builder = new ChildBuilder('child', new StringElementBuilder());
        $builder->extractor(new Getter('public', function ($value) use(&$args) {
            $args = func_get_args();

            return strtoupper($value);
        }));

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $input->import(new GetterTestEntity('my value'));

        $this->assertSame('MY VALUE', $input->element()->value());
        $this->assertCount(2, $args);
        $this->assertSame($input, $args[1]);
    }

    /**
     *
     */
    public function test_with_custom_accessor()
    {
        $builder = new ChildBuilder('child', new StringElementBuilder());
        $builder->extractor($getter = new Getter(null, null, function ($entity) use(&$args) {
            $args = func_get_args();

            return $entity->public;
        }));

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $input->import(new GetterTestEntity('my value'));

        $this->assertSame('my value', $input->element()->value());
        $this->assertCount(4, $args);
        $this->assertNull($args[1]);
        $this->assertSame(ExtractorInterface::EXTRACTION, $args[2]);
        $this->assertSame($getter, $args[3]);
    }

    /**
     *
     */
    public function test_getter()
    {
        $builder = new ChildBuilder('private', new StringElementBuilder());
        $builder->extractor(new Getter());

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $input->import(new GetterTestEntity(null, 'my value'));

        $this->assertSame('my value', $input->element()->value());
    }

    /**
     *
     */
    public function test_embedded_property()
    {
        $builder = new ChildBuilder('private', new StringElementBuilder());
        $builder->extractor(new Getter('foo.bar'));

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $entity = new class {
            public $foo;

            public function __construct()
            {
                $this->foo = new class {
                    public $bar = 'my value';
                };
            }
        };

        $input->import($entity);

        $this->assertSame('my value', $input->element()->value());
    }
}

class GetterTestEntity
{
    public $public;
    private $private;

    /**
     * GetterTestEntity constructor.
     *
     * @param $public
     * @param $private
     */
    public function __construct($public = null, $private = null)
    {
        $this->public = $public;
        $this->private = $private;
    }

    public function private()
    {
        return $this->private;
    }
}
