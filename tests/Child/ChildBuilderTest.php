<?php

namespace Bdf\Form\Child;

use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Aggregate\ChildAggregateInterface;
use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Child\Http\HttpFieldsInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\Filter\ClosureFilter;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Form\Registry\Registry;
use Bdf\Form\View\ElementViewInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * Class ChildBuilderTest
 */
class ChildBuilderTest extends TestCase
{
    /**
     * @var ChildBuilder
     */
    private $builder;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->builder = new ChildBuilder('child', new StringElementBuilder());
    }

    /**
     *
     */
    public function test_build_default()
    {
        $child = $this->builder->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertEquals('child', $child->name());
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEmpty($child->dependencies());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $child = $this->builder
            ->transformer(function ($value) { return $value.'A'; })
            ->transformer(function ($value) { return $value.'B'; }, true)
            ->transformer(function ($value) { return $value.'C'; }, false)
            ->buildChild()
        ;
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit(['child' => 'foo']);
        $this->assertSame('fooBAC', $child->element()->value());
    }

    /**
     *
     */
    public function test_required()
    {
        $child = $this->builder->required()->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit([]);
        $this->assertEquals('This value should not be blank.', $child->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $child = $this->builder->required('my message')->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit([]);
        $this->assertEquals('my message', $child->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_constraint()
    {
        $child = $this->builder->required(new Positive())->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit(['child' => '-1']);
        $this->assertEquals('This value should be positive.', $child->error()->global());
    }

    /**
     *
     */
    public function test_hydrator()
    {
        $child = $this->builder->hydrator(new Setter('prop'))->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));
        $child->element()->import('value');

        $target = [];
        $child->fill($target);

        $this->assertEquals(['prop' => 'value'], $target);
    }

    /**
     *
     */
    public function test_extractor()
    {
        $child = $this->builder->extractor(new Getter('prop'))->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $target = ['prop' => 'value'];
        $child->import($target);

        $this->assertEquals('value', $child->element()->value());
    }

    /**
     *
     */
    public function test_addFilter()
    {
        $child = $this->builder
            ->filter(function ($value) { return $value.'a'; })
            ->filter(function ($value) { return $value.'b'; })
            ->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit(['child' => '']);
        $this->assertEquals('ab', $child->element()->value());
    }

    /**
     *
     */
    public function test_addFilter_prepend()
    {
        $child = $this->builder
            ->filter(function ($value) { return $value.'a'; }, false)
            ->filter(function ($value) { return $value.'b'; }, false)
            ->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit(['child' => '']);
        $this->assertEquals('ba', $child->element()->value());
    }

    /**
     *
     */
    public function test_default()
    {
        $child = $this->builder->default('default')->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit([]);

        $this->assertEquals('default', $child->element()->value());
    }

    /**
     *
     */
    public function test_default_with_value()
    {
        $child = $this->builder->default('default')->value('value')->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $this->assertEquals('value', $child->element()->value());

        $child->submit([]);
        $this->assertEquals('default', $child->element()->value());
    }

    /**
     *
     */
    public function test_default_with_transformer_should_be_applied()
    {
        $child = $this->builder->default('default')
            ->transformer(function ($value, $input, $toPhp) { return $toPhp ? base64_decode($value) : base64_encode($value); })
            ->buildChild()
        ;

        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit([]);

        $this->assertEquals('default', $child->element()->value());
        $this->assertEquals(base64_encode('default'), $child->element()->httpValue());
    }

    /**
     *
     */
    public function test_depends()
    {
        $child = $this->builder->depends('dep1', 'dep2')->buildChild();

        $this->assertEquals(['dep1' => 'dep1', 'dep2' => 'dep2'], $child->dependencies());
    }

    /**
     *
     */
    public function test_setter()
    {
        $child = $this->builder->setter('prop')->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));
        $child->element()->import('value');

        $target = [];
        $child->fill($target);

        $this->assertEquals(['prop' => 'value'], $target);
    }

    /**
     *
     */
    public function test_getter()
    {
        $child = $this->builder->getter('prop')->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $target = ['prop' => 'value'];
        $child->import($target);

        $this->assertEquals('value', $child->element()->value());
    }

    /**
     *
     */
    public function test_getset()
    {
        $child = $this->builder->getset('prop')->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $target = ['prop' => 'value'];
        $child->import($target);
        $this->assertEquals('value', $child->element()->value());

        $target = [];
        $child->fill($target);

        $this->assertEquals(['prop' => 'value'], $target);
    }

    /**
     *
     */
    public function test_childClassName()
    {
        $this->assertInstanceOf(MyCustomChild::class, $this->builder->childClassName(MyCustomChild::class)->buildChild());
    }

    /**
     *
     */
    public function test_addParametersConfigurator_with_child_instance()
    {
        $child = $this->createMock(ChildInterface::class);

        $this->assertSame($child, $this->builder->addParametersConfigurator(function (ChildParameters $parameters) use($child) { $parameters->child = $child; })->buildChild());
    }

    /**
     *
     */
    public function test_addParametersConfigurator_with_child_wrapper()
    {
        $this->assertSame($this->builder, $this->builder->addParametersConfigurator(function (ChildParameters $parameters) {
            $parameters->factories[] = function (ChildParameters $parameters) {
                return new MyCustomChild($parameters->child, 'A');
            };
        }));

        $this->assertSame($this->builder, $this->builder->addParametersConfigurator(function (ChildParameters $parameters) {
            $parameters->factories[] = function (ChildParameters $parameters) {
                return new MyCustomChild($parameters->child, 'B');
            };
        }));

        $child = $this->builder->buildChild();

        $this->assertInstanceOf(MyCustomChild::class, $child);
        $this->assertEquals('B', $child->name);
        $this->assertEquals('A', $child->child->name);
        $this->assertInstanceOf(Child::class, $child->child->child);
    }

    /**
     *
     */
    public function test_trim()
    {
        $child = $this->builder->trim(true)->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit(['child' => '  a  ']);
        $this->assertEquals('a', $child->element()->value());

        $child = $this->builder->trim(false)->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit(['child' => '  a  ']);
        $this->assertEquals('  a  ', $child->element()->value());

        $child = $this->builder->trim()->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit(['child' => '  a  ']);
        $this->assertEquals('a', $child->element()->value());

    }

    /**
     *
     */
    public function test_prefix_default()
    {
        $this->builder = new ChildBuilder('child', new ArrayElementBuilder());
        $child = $this->builder->prefix()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $child->setParent($form = new Form(new ChildrenCollection()));
        $child->submit(['child_0' => 'foo', 'child_1' => 'bar']);
        $this->assertSame(['foo', 'bar'], $child->element()->value());
    }

    /**
     *
     */
    public function test_prefix_explicit()
    {
        $this->builder = new ChildBuilder('child', new ArrayElementBuilder());
        $child = $this->builder->prefix('prefix_')->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $child->setParent($form = new Form(new ChildrenCollection()));
        $child->submit(['prefix_0' => 'foo', 'prefix_1' => 'bar']);
        $this->assertSame(['foo', 'bar'], $child->element()->value());
    }

    /**
     *
     */
    public function test_prefix_empty()
    {
        $this->builder = new ChildBuilder('child', new ArrayElementBuilder());
        $child = $this->builder->prefix('')->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $child->setParent($form = new Form(new ChildrenCollection()));
        $child->submit(['foo', 'bar']);
        $this->assertSame(['foo', 'bar'], $child->element()->value());
    }

    /**
     *
     */
    public function test_httpFields()
    {
        $fields = $this->createMock(HttpFieldsInterface::class);

        $child = $this->builder->httpFields($fields)->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $fields->expects($this->once())->method('extract')->with(['foo' => 'bar'])->willReturn('bar');

        $child->submit(['foo' => 'bar']);
        $this->assertSame('bar', $child->element()->value());
    }

    /**
     *
     */
    public function test_configure()
    {
        $this->builder->configure(function ($builder) use(&$param) { $param = $builder; });

        $this->assertInstanceOf(StringElementBuilder::class, $param);
    }

    /**
     *
     */
    public function test_modelTransformer()
    {
        $child = $this->builder
            ->modelTransformer(function ($value, $input, $toPhp) {
                return $toPhp ? base64_encode($value) : base64_decode($value);
            })
            ->setter('prop')->getter('prop')
            ->buildChild()
        ;
        $child->setParent($form = new Form(new ChildrenCollection()));
        $child->element()->import('value');

        $target = [];
        $child->fill($target);

        $target = ['prop' => base64_encode('foo')];
        $child->import($target);

        $this->assertEquals('foo', $child->element()->value());
    }

    /**
     *
     */
    public function test_protected_fields()
    {
        $builder = new class('child', new StringElementBuilder()) extends ChildBuilder {
            public function test()
            {
                TestCase::assertInstanceOf(StringElementBuilder::class, $this->getElementBuilder());
                TestCase::assertInstanceOf(Registry::class, $this->registry());
            }
        };

        $builder->test();
    }

    /**
     *
     */
    public function test_addFilterProvider()
    {
        $builder = new class('child', new StringElementBuilder()) extends ChildBuilder {
            public function test()
            {
                $this->addFilterProvider(function($registry) {
                    TestCase::assertInstanceOf(Registry::class, $registry);

                    return [];
                });

                $this->addFilterProvider(function () {
                    return [
                        new ClosureFilter(function($v) { return $v.'A'; }),
                        new ClosureFilter(function($v) { return $v.'B'; }),
                    ];
                });

                $this->addFilterProvider(function () {
                    return [
                        new ClosureFilter(function($v) { return $v.'C'; }),
                    ];
                });
            }
        };

        $builder->test();
        $child = $builder->buildChild();
        $child->setParent($form = new Form(new ChildrenCollection()));

        $child->submit(['child' => '']);
        $this->assertEquals('ABC', $child->element()->value());
    }
}

class MyCustomChild implements ChildInterface
{
    public $child;
    public $name;

    public function __construct($child = null, $name = null)
    {
        $this->child = $child;
        $this->name = $name;
    }

    public function element(): ElementInterface
    {
    }

    public function parent(): ChildAggregateInterface
    {
    }

    public function setParent(ChildAggregateInterface $parent): ChildInterface
    {
    }

    public function name(): string
    {
    }

    public function dependencies(): array
    {
    }

    public function import($entity): void
    {
    }

    public function fill(&$entity): void
    {
    }

    public function submit($data): bool
    {
    }

    public function patch($data): bool
    {
    }

    public function httpFields(): array
    {
    }

    public function error(?HttpFieldPath $field = null): FormError
    {
    }

    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
    }
}
