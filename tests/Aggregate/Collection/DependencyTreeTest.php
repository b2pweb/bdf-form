<?php

namespace Bdf\Form\Aggregate\Collection;


use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Leaf\StringElement;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

/**
 *
 */
class DependencyTreeTest extends TestCase
{
    /**
     *
     */
    public function test_add_to_lvl0()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement());

        $tree->add($e1);
        $tree->add($e2);

        $this->assertSame([
            'e1' => $e1,
            'e2' => $e2
        ], $tree->all());

        $this->assertSame([
            'e1' => 0,
            'e2' => 0
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_add_to_lvl1()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);

        $tree->add($e1);
        $tree->add($e2);

        $this->assertSame([
            'e1' => $e1,
            'e2' => $e2
        ], $tree->all());

        $this->assertSame([
            'e1' => 1,
            'e2' => 0
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_add_shift_recursive_dep()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1', 'e2']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertSame([
            'e1' => $e1,
            'e2' => $e2,
            'e3' => $e3
        ], $tree->all());

        $this->assertSame([
            'e1' => 2,
            'e2' => 1,
            'e3' => 0
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_add_shift_multi_dep()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement());
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1', 'e2']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertSame([
            'e1' => 1,
            'e2' => 1,
            'e3' => 0
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_add_no_shift_already_on_higher_level()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertSame([
            'e1' => 1,
            'e2' => 0,
            'e3' => 0
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_add_shift_only_needed_dep()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement());
        $e4 = new Child('e4', new StringElement(), null, [], null, null, null, ['e1', 'e3']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);
        $tree->add($e4);

        $this->assertSame([
            'e1' => 1,
            'e2' => 0,
            'e3' => 1,
            'e4' => 0
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_add_with_unregistered_dep()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement(), null, [], null, null, null, ['d1', 'd2']);
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['d1', 'd3']);

        $tree->add($e1);
        $tree->add($e2);

        $this->assertSame([
            'e1' => 0,
            'd1' => 1,
            'd2' => 1,
            'e2' => 0,
            'd3' => 1
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_add_dependencies_after()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement(), null, [], null, null, null, ['d1', 'd2']);
        $d1 = new Child('d1', new StringElement(), null, [], null, null, null, ['d2']);

        $tree->add($e1);
        $tree->add($d1);

        $this->assertSame([
            'e1' => 0,
            'd1' => 1,
            'd2' => 2,
        ], $this->getAttributeValue($tree, 'depth'));

        $tree->add(new Child('d2', new StringElement()));

        $this->assertSame([
            'e1' => 0,
            'd1' => 1,
            'd2' => 2,
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_array_access()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $tree->add($e1);

        $this->assertTrue(isset($tree['e1']));
        $this->assertFalse(isset($tree['e2']));
        $this->assertSame($e1, $tree['e1']);

        $tree[] = new Child('e2', new StringElement());
        $this->assertTrue(isset($tree['e2']));

        unset($tree['e1']);
        $this->assertFalse(isset($tree['e1']));
    }

    /**
     *
     */
    public function test_iterator()
    {

        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $tree->add($e1);

        $this->assertInstanceOf(\ArrayIterator::class, $tree->getIterator());

        $this->assertEquals(['e1' => $e1], iterator_to_array($tree));
        $this->assertEquals(['e1' => $e1], iterator_to_array($tree->reverseIterator()));
        $this->assertEquals(['e1' => $e1], iterator_to_array($tree->forwardIterator()));
    }

    /**
     *
     */
    public function test_remove_indexed_dep()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertTrue($tree->remove('e1'));

        $this->assertFalse($tree->has('e1'));
    }

    /**
     *
     */
    public function test_remove_not_dep()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertTrue($tree->remove('e2'));

        $this->assertFalse($tree->has('e2'));
    }

    /**
     *
     */
    public function test_remove_not_registered()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertFalse($tree->remove('not_found'));
    }

    /**
     *
     */
    public function test_add_complex()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement(), null, [], null, null, null, ['e2', 'e3']);
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e3']);
        $e3 = new Child('e3', new StringElement());
        $e4 = new Child('e4', new StringElement(), null, [], null, null, null, ['e3', 'e2', 'e5']);
        $e5 = new Child('e5', new StringElement());

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);
        $tree->add($e4);
        $tree->add($e5);

        $this->assertSame([
            'e1' => 0,
            'e2' => 1,
            'e3' => 2,
            'e4' => 0,
            'e5' => 1
        ], $this->getAttributeValue($tree, 'depth'));
    }

    /**
     *
     */
    public function test_duplicate()
    {
        $collection = new DependencyTree();
        $baseParent = new Form(new ChildrenCollection());

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement());
        $e3 = new Child('e3', new StringElement());

        $e1->setParent($baseParent);
        $e2->setParent($baseParent);
        $e3->setParent($baseParent);

        $collection->add($e1);
        $collection->add($e2);
        $collection->add($e3);

        $newParent = new Form(new ChildrenCollection());
        $duplicated = $collection->duplicate($newParent);

        $this->assertSame($baseParent, $collection['e1']->parent());
        $this->assertSame($baseParent, $collection['e2']->parent());
        $this->assertSame($baseParent, $collection['e3']->parent());

        $this->assertNotSame($collection, $duplicated);
        $this->assertCount(3, $duplicated);

        $this->assertSame($newParent, $duplicated['e1']->parent());
        $this->assertSame($newParent, $duplicated['e2']->parent());
        $this->assertSame($newParent, $duplicated['e3']->parent());
    }

    public function getAttributeValue($object, string $attributeName)
    {
        $reflector = new ReflectionObject($object);
        $prop = $reflector->getProperty($attributeName);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}
