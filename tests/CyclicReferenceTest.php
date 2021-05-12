<?php

namespace Bdf\Form;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use PHPUnit\Framework\TestCase;
use WeakReference;

/**
 * Class CyclicReferenceTest
 *
 * @requires PHP 7.4
 */
class CyclicReferenceTest extends TestCase
{
    /**
     *
     */
    public function test_simple()
    {
        $form = new MyCustomForm();

        $this->assertSame(1, MyCustomForm::$count);

        unset($form);
        $this->assertSame(0, MyCustomForm::$count);
    }

    /**
     *
     */
    public function test_children_should_be_destroyed()
    {
        $form = new MyCustomForm();
        $form->submit([
            'foo' => 'foo',
            'bar' => 42,
            'embedded' => [
                'foo' => 'foo2'
            ]
        ]);
        $form->value();

        $foo = WeakReference::create($form['foo']);
        $fooElement = WeakReference::create($form['foo']->element());
        $bar = WeakReference::create($form['bar']);
        $barElement = WeakReference::create($form['bar']->element());
        $embedded = WeakReference::create($form['embedded']);
        $embeddedElement = WeakReference::create($form['embedded']->element());
        $embeddedFoo = WeakReference::create($form['embedded']->element()['foo']);
        $embeddedFooElement = WeakReference::create($form['embedded']->element()['foo']->element());

        unset($form);

        $this->assertNull($foo->get());
        $this->assertNull($fooElement->get());
        $this->assertNull($bar->get());
        $this->assertNull($barElement->get());
        $this->assertNull($embedded->get());
        $this->assertNull($embeddedElement->get());
        $this->assertNull($embeddedFoo->get());
        $this->assertNull($embeddedFooElement->get());
    }

    /**
     *
     */
    public function test_generate_value()
    {
        $form = new MyCustomForm();
        $form->submit([
            'foo' => 'foo',
            'bar' => 42,
            'embedded' => [
                'foo' => 'foo2'
            ]
        ]);

        $entity = $form->value();

        $this->assertSame(1, MyCustomForm::$count);
        $this->assertSame(2, MyCustomEntity::$count);

        unset($form, $entity);
        $this->assertSame(0, MyCustomForm::$count);
        $this->assertSame(0, MyCustomEntity::$count);
    }

    /**
     *
     */
    public function test_import()
    {
        $form = new MyCustomForm();
        $form->import($entity = new MyCustomEntity());

        $this->assertSame(1, MyCustomForm::$count);
        $this->assertSame(1, MyCustomEntity::$count);

        $this->assertSame($entity, $form->value());

        unset($form);
        $this->assertSame(0, MyCustomForm::$count);
        $this->assertSame(1, MyCustomEntity::$count);

        unset($entity);
        $this->assertSame(0, MyCustomEntity::$count);
    }
}

class MyCustomForm extends CustomForm
{
    public static $count = 0;

    public function __construct(?FormBuilderInterface $builder = null)
    {
        parent::__construct($builder);

        ++self::$count;
    }

    public function __destruct()
    {
        --self::$count;
    }

    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->generates(MyCustomEntity::class);

        $builder
            ->string('foo')
            ->length(['min' => 3])
            ->setter()->getter()
        ;

        $builder
            ->integer('bar')
            ->setter()->getter()
        ;

        $builder->embedded('embedded', function (ChildBuilderInterface $builder) {
            $builder->generates(MyCustomEntity::class);
            $builder->string('foo')
                ->getter()->setter()
            ;
        })->setter();
    }
}

class MyCustomEntity
{
    public static $count = 0;

    public function __construct()
    {
        ++self::$count;
    }

    public function __destruct()
    {
        --self::$count;
    }

    public function __clone()
    {
        ++self::$count;
    }

    /**
     * @var string
     */
    public $foo;

    /**
     * @var int
     */
    public $bar;

    /**
     * @var MyCustomEntity
     */
    public $embedded;
}
