<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class TransformerAggregateTest
 */
class TransformerAggregateTest extends TestCase
{
    /**
     *
     */
    public function test_transformToHttp()
    {
        $transformer = new TransformerAggregate([
            new ClosureTransformer(function ($value) { return $value.'a'; }),
            new ClosureTransformer(function ($value) { return $value.'b'; }),
        ]);

        $element = $this->createMock(ElementInterface::class);

        $this->assertEquals('vab', $transformer->transformToHttp('v', $element));
    }

    /**
     *
     */
    public function test_transformFromHttp()
    {
        $transformer = new TransformerAggregate([
            new ClosureTransformer(function ($value) { return $value.'a'; }),
            new ClosureTransformer(function ($value) { return $value.'b'; }),
        ]);

        $element = $this->createMock(ElementInterface::class);

        $this->assertEquals('vba', $transformer->transformFromHttp('v', $element));
    }

    /**
     *
     */
    public function test_append()
    {
        $transformer = new TransformerAggregate([
            new ClosureTransformer(function ($value) { return $value.'a'; }),
        ]);
        $transformer->append(new ClosureTransformer(function ($value) { return $value.'b'; }));

        $this->assertEquals('vab', $transformer->transformToHttp('v', $this->createMock(ElementInterface::class)));
    }

    /**
     *
     */
    public function test_prepend()
    {
        $transformer = new TransformerAggregate([
            new ClosureTransformer(function ($value) { return $value.'a'; }),
        ]);
        $transformer->prepend(new ClosureTransformer(function ($value) { return $value.'b'; }));

        $this->assertEquals('vba', $transformer->transformToHttp('v', $this->createMock(ElementInterface::class)));
    }
}
