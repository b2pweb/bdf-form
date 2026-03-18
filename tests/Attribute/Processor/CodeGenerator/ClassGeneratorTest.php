<?php

namespace Tests\Form\Attribute\Processor\CodeGenerator;

use Bdf\Form\Attribute\Processor\CodeGenerator\ClassGenerator;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\TestCase;

class ClassGeneratorTest extends TestCase
{
    /**
     * @return void
     */
    public function test_implements()
    {
        $generator = new ClassGenerator(new PhpNamespace('Generated'), new ClassType('Foo'));
        $generator->implements(PostConfigureInterface::class);

        $this->assertEquals(<<<'PHP'
class Foo implements PostConfigureInterface
{
}

PHP
        , $generator->generateClass()
);

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Attribute\Processor\PostConfigureInterface;


PHP
        , $generator->printer()->printNamespace($generator->namespace())
);
    }

    /**
     * @return void
     */
    public function test_implementsMethod()
    {
        $generator = new ClassGenerator(new PhpNamespace('Generated'), new ClassType('Foo'));
        $method = $generator->implementsMethod(PostConfigureInterface::class, 'postConfigure');

        $this->assertEquals('postConfigure', $method->getName());

        $this->assertEquals(<<<'PHP'
class Foo
{
    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
    }
}

PHP
        , $generator->generateClass()
);

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;


PHP
        , $generator->printer()->printNamespace($generator->namespace())
);
    }

    /**
     * @return void
     */
    public function test_useAndSimplifyType()
    {
        $generator = new ClassGenerator(new PhpNamespace('Generated'), new ClassType('Foo'));

        $this->assertSame('StringElement', $generator->useAndSimplifyType(StringElement::class));
        $this->assertSame('WithAlias', $generator->useAndSimplifyType(IntegerElement::class, 'WithAlias'));

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Leaf\IntegerElement as WithAlias;
use Bdf\Form\Leaf\StringElement;


PHP
            , $generator->printer()->printNamespace($generator->namespace())
        );
    }

    /**
     * @return void
     */
    public function test_use()
    {
        $generator = new ClassGenerator(new PhpNamespace('Generated'), new ClassType('Foo'));

        $generator->use(StringElement::class)->use(IntegerElement::class, 'WithAlias');

        $this->assertEquals(<<<'PHP'
namespace Generated;

use Bdf\Form\Leaf\IntegerElement as WithAlias;
use Bdf\Form\Leaf\StringElement;


PHP
            , $generator->printer()->printNamespace($generator->namespace())
        );
    }

    /**
     * @return void
     */
    public function test_getters()
    {
        $namespace = new PhpNamespace('Generated');
        $class = new ClassType('Foo');
        $printer = new PsrPrinter();

        $generator = new ClassGenerator($namespace, $class, $printer);

        $this->assertSame($namespace, $generator->namespace());
        $this->assertSame($class, $generator->class());
        $this->assertSame($printer, $generator->printer());
    }
}
