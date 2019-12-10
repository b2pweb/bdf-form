<?php

namespace Bdf\Form\Aggregate\Collection;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class LevelTest extends TestCase
{
    /**
     *
     */
    public function test_add_no_dep()
    {
         $level = new Level(null, 2);

         $this->assertEquals(['e1' => 2], $level->add('e1', []));

         $this->assertTrue($level->has('e1'));
    }

    /**
     *
     */
    public function test_shift()
    {
        $level = new Level();

        $level->add('e1', []);
        $this->assertEquals(['e1' => 1], $level->shift('e1'));

        $this->assertFalse($level->has('e1'));
        $this->assertNotNull($level->next());
        $this->assertSame($level->next(), $level->last());
        $this->assertEquals(1, $level->next()->number());
        $this->assertSame($level, $level->next()->prev());
        $this->assertTrue($level->next()->has('e1'));
    }

    /**
     *
     */
    public function test_add_with_dep()
    {
        $level = new Level();

        $this->assertEquals([
            'e1' => 0,
            'd1' => 1,
            'd2' => 1
        ], $level->add('e1', ['d1', 'd2']));

        $this->assertTrue($level->has('e1'));
        $this->assertTrue($level->next()->has('d1'));
        $this->assertTrue($level->next()->has('d2'));
    }

    /**
     *
     */
    public function test_add_will_shift_dep()
    {
        $level = new Level();

        $level->add('e1', []);
        $level->add('e2', []);
        $this->assertEquals([
            'e3' => 0,
            'e1' => 1,
            'e2' => 1
        ], $level->add('e3', ['e1', 'e2']));

        $this->assertTrue($level->has('e3'));
        $this->assertTrue($level->next()->has('e1'));
        $this->assertTrue($level->next()->has('e2'));
    }

    /**
     *
     */
    public function test_iterator()
    {
        $level = new Level();

        $level->add('e1', ['d1', 'd2']);
        $level->add('e2', []);
        $level->add('e3', ['d1']);

        $this->assertEquals([
            'e1' => ['d1', 'd2'],
            'e2' => [],
            'e3' => ['d1'],
        ], iterator_to_array($level));
    }

    /**
     *
     */
    public function test_reset()
    {
        $level = new Level();

        $level->add('e1', ['d1', 'd2']);
        $level->reset('e1');

        $this->assertTrue($level->has('e1'));

        $this->assertEquals(['e1' => []], iterator_to_array($level));
    }

    /**
     *
     */
    public function test_remove()
    {
        $level = new Level();

        $level->add('e1', ['d1', 'd2']);
        $level->remove('e1');

        $this->assertFalse($level->has('e1'));
    }
}
