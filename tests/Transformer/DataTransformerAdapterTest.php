<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class DataTransformerAdapterTest
 */
class DataTransformerAdapterTest extends TestCase
{
    /**
     *
     */
    public function test_transformToHttp()
    {
        $sfTransformer = $this->createMock(DataTransformerInterface::class);
        $element = $this->createMock(ElementInterface::class);

        $transformer = new DataTransformerAdapter($sfTransformer);

        $sfTransformer->expects($this->once())->method('transform')->with('value')->willReturn('transformed');

        $this->assertSame('transformed', $transformer->transformToHttp('value', $element));
    }

    /**
     *
     */
    public function test_transformFromHttp()
    {
        $sfTransformer = $this->createMock(DataTransformerInterface::class);
        $element = $this->createMock(ElementInterface::class);

        $transformer = new DataTransformerAdapter($sfTransformer);

        $sfTransformer->expects($this->once())->method('reverseTransform')->with('value')->willReturn('transformed');

        $this->assertSame('transformed', $transformer->transformFromHttp('value', $element));
    }

    /**
     *
     */
    public function test_getTransformer()
    {
        $sfTransformer = $this->createMock(DataTransformerInterface::class);

        $transformer = new DataTransformerAdapter($sfTransformer);

        $this->assertSame($sfTransformer, $transformer->getTransformer());
    }
}
