<?php

namespace Bdf\Form\Aggregate\Collection;

use Bdf\Form\Child\Child;
use Bdf\Form\Leaf\StringElement;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DependencyIteratorTest extends TestCase
{
    /**
     *
     */
    public function test_next_will_skip_empty_levels()
    {
        $root = new Level();
        $lvl1 = new Level($root, 1);
        $lvl2 = new Level($lvl1, 2);
        $lvl3 = new Level($lvl2, 3);

        $lvl3->add('e2', []);
        $root->add('e1', []);

        $it = new DependencyIterator([
            'e1' => new Child('e1', new StringElement()),
            'e2' => new Child('e2', new StringElement()),
        ], $lvl3);

        $it->rewind();

        $this->assertEquals('e2', $it->key());
        $this->assertEquals(new Child('e2', new StringElement()), $it->current());

        $it->next();

        $this->assertTrue($it->valid());
        $this->assertEquals('e1', $it->key());
        $this->assertEquals(new Child('e1', new StringElement()), $it->current());

        $it->next();
        $this->assertFalse($it->valid());
    }

    /**
     *
     */
    public function test_next_will_skip_unregistered_elements()
    {
        $root = new Level();

        $root->add('e1', ['d1']);
        $root->next()->add('d2', []);
        $root->next()->add('d3', []);

        $it = new DependencyIterator([
            'e1' => new Child('e1', new StringElement()),
            'd1' => new Child('d1', new StringElement()),
        ], $root->last());

        $it->rewind();

        $this->assertEquals('d1', $it->key());
        $this->assertEquals(new Child('d1', new StringElement()), $it->current());

        $it->next();

        $this->assertEquals('e1', $it->key());
        $this->assertEquals(new Child('e1', new StringElement()), $it->current());
    }

    /**
     *
     */
    public function test_rewind_will_skip_unregistered_elements()
    {
        $root = new Level();

        $root->add('e1', ['d1', 'd2']);

        $it = new DependencyIterator([
            'e1' => new Child('e1', new StringElement()),
            'd2' => new Child('d2', new StringElement()),
        ], $root->last());

        $it->rewind();

        $this->assertEquals('d2', $it->key());
        $this->assertEquals(new Child('d2', new StringElement()), $it->current());
    }

    /**
     *
     */
    public function test_rewind_will_skip_empty_levels()
    {
        $root = new Level();
        $lvl1 = new Level($root, 1);
        $lvl2 = new Level($lvl1, 2);
        $lvl3 = new Level($lvl2, 3);

        $root->add('e1', []);

        $it = new DependencyIterator([
            'e1' => new Child('e1', new StringElement()),
        ], $lvl3);

        $it->rewind();

        $this->assertEquals('e1', $it->key());
        $this->assertEquals(new Child('e1', new StringElement()), $it->current());

        $it->next();
        $this->assertFalse($it->valid());
    }

    /**
     *
     */
    public function test_empty_levels_will_not_be_valid()
    {
        $root = new Level();
        $lvl1 = new Level($root, 1);
        $lvl2 = new Level($lvl1, 2);
        $lvl3 = new Level($lvl2, 3);

        $it = new DependencyIterator([], $lvl3);

        $it->rewind();
        $this->assertFalse($it->valid());
    }

    /**
     *
     */
    public function test_only_unregistered_elements_will_not_be_valid()
    {
        $root = new Level();

        $root->add('e1', ['e2', 'e3']);
        $root->add('e2', ['e3']);

        $it = new DependencyIterator([], $root->last());

        $it->rewind();
        $this->assertFalse($it->valid());
    }

    /**
     *
     */
    public function test_functional_without_dep()
    {
        $tree = new DependencyTree();

        $tree->add($e1 = new Child('e1', new StringElement()));
        $tree->add($e2 = new Child('e2', new StringElement()));
        $tree->add($e3 = new Child('e3', new StringElement()));

        $this->assertEquals([
            'e1' => $e1,
            'e2' => $e2,
            'e3' => $e3,
        ], iterator_to_array($tree->getIterator()));
    }

    /**
     *
     */
    public function test_functional_with_registered_dep()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement(), null, [], null, null, null, ['e2', 'e3']);
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e3']);
        $e3 = new Child('e3', new StringElement());

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);

        $arr = iterator_to_array($tree->reverseIterator());
        $this->assertEquals([
            'e1' => $e1,
            'e2' => $e2,
            'e3' => $e3,
        ], $arr);

        $this->assertEquals(['e3', 'e2', 'e1'], array_keys($arr));
    }

    /**
     *
     */
    public function test_functional_with_unregistered_dep()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement(), null, [], null, null, null, ['e2', 'e3']);
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e3']);

        $tree->add($e1);
        $tree->add($e2);

        $this->assertEquals([
            'e1' => $e1,
            'e2' => $e2,
        ], iterator_to_array($tree->getIterator()));
    }

    /**
     *
     */
    public function test_functional_check_order_on_complex_tree()
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

        $this->assertEquals([
            'e3',       //lvl2
            'e2', 'e5', //lvl1
            'e1', 'e4'  //lvl0
        ], array_keys(iterator_to_array($tree->reverseIterator())));
    }

    /**
     *
     */
    public function test_functional_check_order_on_complex_tree_forward()
    {
        $tree = new DependencyTree();

        $e1 = new Child('e1', new StringElement(), null, [], null, null, null, ['e2', 'e3']);
        $e2 = new Child('e2', new StringElement(), null, [], null, null, null, ['e3']);
        $e3 = new Child('e3', new StringElement());
        $e4 = new Child('e4', new StringElement(), null, [], null, null, null, ['e2', 'e3', 'e5']);
        $e5 = new Child('e5', new StringElement());

        $tree->add($e1);
        $tree->add($e2);
        $tree->add($e3);
        $tree->add($e4);
        $tree->add($e5);

        $this->assertEquals([
            'e1', 'e4', //lvl0
            'e2', 'e5', //lvl1
            'e3',       //lvl2
        ], array_keys(iterator_to_array($tree->forwardIterator())));
    }
}
