<?php

namespace Tests\Form\Attribute\Processor\CodeGenerator;

use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\TransformerClassGenerator;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;

class TransformerClassGeneratorTest extends TestCase
{
    /**
     * @return void
     */
    public function test_empty()
    {
        $generator = new TransformerClassGenerator(new PhpNamespace('Generated'));

        $this->assertEquals(<<<'PHP'
implements TransformerInterface {
    /**
     * {@inheritdoc}
     */
    function transformToHttp($value, ElementInterface $input)
    {
    }

    /**
     * {@inheritdoc}
     */
    function transformFromHttp($value, ElementInterface $input)
    {
    }
}
PHP
        , $generator->generateClass()
);

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;


PHP
        , $generator->printer()->printNamespace($generator->namespace())
);
    }

    /**
     * @return void
     */
    public function test_withPromotedProperty()
    {
        $generator = new TransformerClassGenerator(new PhpNamespace('Generated'));
        $generator->withPromotedProperty('foo')->setPrivate()->setType(FormInterface::class);

        $this->assertEquals(<<<'PHP'
implements TransformerInterface {
    /**
     * {@inheritdoc}
     */
    function transformToHttp($value, ElementInterface $input)
    {
    }

    /**
     * {@inheritdoc}
     */
    function transformFromHttp($value, ElementInterface $input)
    {
    }

    public function __construct(
        private \Bdf\Form\Aggregate\FormInterface $foo,
    ) {
    }
}
PHP
        , $generator->generateClass()
);

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;


PHP
        , $generator->printer()->printNamespace($generator->namespace())
);
    }

    /**
     * @return void
     */
    public function test_with_methods_body()
    {
        $generator = new TransformerClassGenerator(new PhpNamespace('Generated'));

        $generator->toHttp()->setBody('return $value + 2;');
        $generator->fromHttp()->setBody('return $value - 2;');

        $this->assertEquals(<<<'PHP'
implements TransformerInterface {
    /**
     * {@inheritdoc}
     */
    function transformToHttp($value, ElementInterface $input)
    {
        return $value + 2;
    }

    /**
     * {@inheritdoc}
     */
    function transformFromHttp($value, ElementInterface $input)
    {
        return $value - 2;
    }
}
PHP
            , $generator->generateClass()
        );

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;


PHP
            , $generator->printer()->printNamespace($generator->namespace())
        );
    }
}
