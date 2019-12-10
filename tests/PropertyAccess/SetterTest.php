<?php

namespace Bdf\Form\PropertyAccess;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Leaf\StringElementBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class SetterTest
 */
class SetterTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $builder = new ChildBuilder('public', new StringElementBuilder());
        $builder->hydrator(new Setter())->value('my value');

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $entity = new SetterTestEntity();
        $input->fill($entity);

        $this->assertSame('my value', $entity->public);
    }

    /**
     *
     */
    public function test_custom_property_name()
    {
        $builder = new ChildBuilder('child', new StringElementBuilder());
        $builder->hydrator(new Setter('public'))->value('my value');

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $entity = new SetterTestEntity();
        $input->fill($entity);

        $this->assertSame('my value', $entity->public);
    }

    /**
     *
     */
    public function test_with_transformer()
    {
        $builder = new ChildBuilder('public', new StringElementBuilder());
        $builder->hydrator(new Setter(function ($value) use(&$args) {
            $args = func_get_args();

            return strtoupper($value);
        }))->value('my value');

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $entity = new SetterTestEntity();
        $input->fill($entity);

        $this->assertSame('MY VALUE', $entity->public);
        $this->assertCount(2, $args);
        $this->assertSame($input, $args[1]);
    }

    /**
     *
     */
    public function test_with_property_name_and_transformer()
    {
        $builder = new ChildBuilder('child', new StringElementBuilder());
        $builder->hydrator(new Setter('public', function ($value) use(&$args) {
            $args = func_get_args();

            return strtoupper($value);
        }))->value('my value');

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $entity = new SetterTestEntity();
        $input->fill($entity);

        $this->assertSame('MY VALUE', $entity->public);
        $this->assertCount(2, $args);
        $this->assertSame($input, $args[1]);
    }

    /**
     *
     */
    public function test_with_custom_accessor()
    {
        $builder = new ChildBuilder('child', new StringElementBuilder());
        $builder->hydrator($setter = new Setter(null, null, function ($entity, $value) use(&$args) {
            $args = func_get_args();

            $entity->public = $value;
        }))->value('my value');

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $entity = new SetterTestEntity();
        $input->fill($entity);

        $this->assertSame('my value', $entity->public);
        $this->assertCount(4, $args);
        $this->assertSame(HydratorInterface::HYDRATION, $args[2]);
        $this->assertSame($setter, $args[3]);
    }

    /**
     *
     */
    public function test_setter()
    {
        $builder = new ChildBuilder('private', new StringElementBuilder());
        $builder->hydrator(new Setter())->value('my value');

        $input = $builder->buildChild();
        $input->setParent(new Form(new ChildrenCollection()));

        $entity = new SetterTestEntity();
        $input->fill($entity);

        $this->assertSame('my value', $entity->private());
    }
}

class SetterTestEntity
{
    public $public;
    private $private;

    public function private()
    {
        return $this->private;
    }

    /**
     * @param mixed $private
     */
    public function setPrivate($private): void
    {
        $this->private = $private;
    }
}
