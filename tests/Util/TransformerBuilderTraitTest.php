<?php

namespace Bdf\Form\Util;

use Bdf\Form\Registry\Registry;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerAggregate;
use Bdf\Form\Transformer\TransformerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class TransformerBuilderTraitTest
 */
class TransformerBuilderTraitTest extends TestCase
{
    /**
     *
     */
    public function test_built_transformer_instance()
    {
        $builder = new class {
            use TransformerBuilderTrait;
            protected function registry(): RegistryInterface { return new Registry(); }
            public function get() { return $this->buildTransformer(); }
        };

        $this->assertSame(NullTransformer::instance(), $builder->get());

        $builder->transformer($t1 = $this->createMock(TransformerInterface::class));

        $this->assertSame($t1, $builder->get());

        $builder->transformer($t2 = $this->createMock(TransformerInterface::class));
        $this->assertEquals(new TransformerAggregate([$t1, $t2]), $builder->get());
    }
}
