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
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
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
    public function test_required()
    {
        $child = $this->builder->required()->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

        $child->submit([]);
        $this->assertEquals('This value should not be blank.', $child->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $child = $this->builder->required('my message')->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

        $child->submit([]);
        $this->assertEquals('my message', $child->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_constraint()
    {
        $child = $this->builder->required(new Positive())->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

        $child->submit(['child' => '-1']);
        $this->assertEquals('This value should be positive.', $child->error()->global());
    }

    /**
     *
     */
    public function test_hydrator()
    {
        $child = $this->builder->hydrator(new Setter('prop'))->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));
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
        $child->setParent(new Form(new ChildrenCollection()));

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
        $child->setParent(new Form(new ChildrenCollection()));

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
        $child->setParent(new Form(new ChildrenCollection()));

        $child->submit(['child' => '']);
        $this->assertEquals('ba', $child->element()->value());
    }

    /**
     *
     */
    public function test_default()
    {
        $child = $this->builder->default('default')->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

        $child->submit([]);

        $this->assertEquals('default', $child->element()->value());
    }

    /**
     *
     */
    public function test_default_with_value()
    {
        $child = $this->builder->default('default')->value('value')->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

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

        $child->setParent(new Form(new ChildrenCollection()));

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
        $child->setParent(new Form(new ChildrenCollection()));
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
        $child->setParent(new Form(new ChildrenCollection()));

        $target = ['prop' => 'value'];
        $child->import($target);

        $this->assertEquals('value', $child->element()->value());
    }

    /**
     *
     */
    public function test_childFactory_with_class_name()
    {
        $this->assertInstanceOf(MyCustomChild::class, $this->builder->childFactory(MyCustomChild::class)->buildChild());
    }

    /**
     *
     */
    public function test_childFactory_with_closure()
    {
        $child = $this->createMock(ChildInterface::class);

        $this->assertSame($child, $this->builder->childFactory(function () use($child) { return $child; })->buildChild());
    }

    /**
     *
     */
    public function test_trim()
    {
        $child = $this->builder->trim(true)->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

        $child->submit(['child' => '  a  ']);
        $this->assertEquals('a', $child->element()->value());

        $child = $this->builder->trim(false)->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

        $child->submit(['child' => '  a  ']);
        $this->assertEquals('  a  ', $child->element()->value());

        $child = $this->builder->trim()->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

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
        $child->setParent(new Form(new ChildrenCollection()));
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
        $child->setParent(new Form(new ChildrenCollection()));
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
        $child->setParent(new Form(new ChildrenCollection()));
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
        $child->setParent(new Form(new ChildrenCollection()));

        $fields->expects($this->once())->method('extract')->with(['foo' => 'bar'], null)->willReturn('bar');

        $child->submit(['foo' => 'bar']);
        $this->assertSame('bar', $child->element()->value());
    }
}

class MyCustomChild implements ChildInterface
{
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
