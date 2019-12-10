<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class NullTransformerTest
 */
class NullTransformerTest extends TestCase
{
    /**
     *
     */
    public function test_instance()
    {
        $this->assertInstanceOf(NullTransformer::class, NullTransformer::instance());
        $this->assertSame(NullTransformer::instance(), NullTransformer::instance());
    }

    /**
     *
     */
    public function test_transform()
    {
        $element = $this->createMock(ElementInterface::class);
        $transformer = new NullTransformer();

        $this->assertSame('value', $transformer->transformFromHttp('value', $element));
        $this->assertSame('value', $transformer->transformToHttp('value', $element));
    }
}
