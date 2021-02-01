<?php

namespace Bdf\Form\Aggregate\Collection;

use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Leaf\StringElement;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ChildrenCollectionTest extends TestCase
{
    /**
     *
     */
    public function test_add_to_lvl0()
    {
        $tree = new ChildrenCollection();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement());

        $tree->add($e1);
        $tree->add($e2);

        $this->assertSame([
            'e1' => $e1,
            'e2' => $e2,
        ], $tree->all());
    }

    /**
     *
     */
    public function test_add_to_lvl1()
    {
        $tree = new ChildrenCollection();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);

        $tree->add($e1);
        $tree->add($e2);

        $this->assertSame([
            'e2' => $e2,
            'e1' => $e1,
        ], $tree->all());
    }

    /**
     *
     */
    public function test_add_with_not_found_dependency_should_be_ignored()
    {
        $tree = new ChildrenCollection();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['not_found', 'e1']);

        $tree->add($e1);
        $tree->add($e2);

        $this->assertSame([
            'e2' => $e2,
            'e1' => $e1,
        ], $tree->all());
    }

    /**
     *
     */
    public function test_add_shift_recursive_dep()
    {
        $tree = new ChildrenCollection();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1', 'e2']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertSame([
            'e3' => $e3,
            'e2' => $e2,
            'e1' => $e1,
        ], $tree->all());
    }

    /**
     *
     */
    public function test_add_shift_multi_dep()
    {
        $tree = new ChildrenCollection();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement());
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1', 'e2']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertSame([
            'e3' => $e3,
            'e1' => $e1,
            'e2' => $e2,
        ], $tree->all());
    }

    /**
     *
     */
    public function test_add_no_shift_already_on_higher_level()
    {
        $tree = new ChildrenCollection();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement(), null, [], null, null, null, ['e1']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $this->assertSame([
            'e2' => $e2,
            'e3' => $e3,
            'e1' => $e1,
        ], $tree->all());
    }

    /**
     *
     */
    public function test_add_shift_only_needed_dep()
    {
        $tree = new ChildrenCollection();

        $e1 = new Child('e1', new StringElement());
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e1']);
        $e3 = new Child('e3', new StringElement());
        $e4 = new Child('e4', new StringElement(), null, [], null, null, null, ['e1', 'e3']);

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);
        $tree->add($e4);

        $this->assertSame([
            'e2' => $e2,
            'e4' => $e4,
            'e1' => $e1,
            'e3' => $e3,
        ], $tree->all());
    }

    /**
     *
     */
    public function test_array_access()
    {
        $tree = new ChildrenCollection();

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
        $tree = new ChildrenCollection();

        $e1 = new Child('e1', new StringElement());
        $tree->add($e1);

        $this->assertEquals(['e1' => $e1], iterator_to_array($tree));
        $this->assertEquals(['e1' => $e1], iterator_to_array($tree->getIterator()));
        $this->assertEquals(['e1' => $e1], iterator_to_array($tree->reverseIterator()));
        $this->assertEquals(['e1' => $e1], iterator_to_array($tree->forwardIterator()));
    }

    /**
     *
     */
    public function test_remove_indexed_dep()
    {
        $tree = new ChildrenCollection();

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
    public function test_remove_not_registered()
    {
        $tree = new ChildrenCollection();

        $tree->add(new Child('e1', new StringElement()));

        $this->assertFalse($tree->remove('not_found'));
    }

    /**
     *
     */
    public function test_duplicate()
    {
        $collection = new ChildrenCollection();
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
}
