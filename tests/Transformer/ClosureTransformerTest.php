<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class ClosureTransformerTest
 */
class ClosureTransformerTest extends TestCase
{
    /**
     *
     */
    public function test_transformToHttp()
    {
        $transformer = new ClosureTransformer(function () use(&$args) { $args = func_get_args(); return 'transformed'; });
        $input = $this->createMock(ElementInterface::class);

        $this->assertSame('transformed', $transformer->transformToHttp('http value', $input));

        $this->assertCount(3, $args);
        $this->assertSame('http value', $args[0]);
        $this->assertSame($input, $args[1]);
        $this->assertFalse($args[2]);
    }

    /**
     *
     */
    public function test_transformFromHttp()
    {
        $transformer = new ClosureTransformer(function () use(&$args) { $args = func_get_args(); return 'transformed'; });
        $input = $this->createMock(ElementInterface::class);

        $this->assertSame('transformed', $transformer->transformFromHttp('http value', $input));

        $this->assertCount(3, $args);
        $this->assertSame('http value', $args[0]);
        $this->assertSame($input, $args[1]);
        $this->assertTrue($args[2]);
    }
}
