<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Constraint\Closure;
use Bdf\Form\Leaf\Fixtures\TestBackedEnum;
use Bdf\Form\Leaf\Fixtures\TestUnitEnum;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\NotBlank;

use function strtolower;
use function strtoupper;
use function ucfirst;

class UnitEnumElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertTrue($element->submit('Foo')->valid());
        $this->assertFalse($element->failed());
        $this->assertSame(TestUnitEnum::Foo, $element->value());
        $this->assertTrue($element->error()->empty());

        $this->assertTrue($element->submit(TestUnitEnum::Bar)->valid());
        $this->assertFalse($element->failed());
        $this->assertSame(TestUnitEnum::Bar, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    #[
        TestWith([false]),
        TestWith([new stdClass()]),
        TestWith([TestBackedEnum::Bar]),
        TestWith(['invalid']),
        TestWith([42]),
        TestWith([42.1]),
    ]
    public function test_submit_invalid(mixed $value)
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertTrue($element->submit($value)->valid());
        $this->assertFalse($element->failed());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new UnitEnumElement(TestUnitEnum::class, new ConstraintValueValidator([new NotBlank()]));

        $this->assertFalse($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertEquals('This value should not be blank.', $element->error()->global());

        $this->assertTrue($element->submit('Foo')->valid());
        $this->assertSame(TestUnitEnum::Foo, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new UnitEnumElement(TestUnitEnum::class, transformer: $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception_ignored()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new UnitEnumElement(
            TestUnitEnum::class,
            validator: new ConstraintValueValidator([], new TransformerExceptionConstraint(ignoreException: true)),
            transformer: $transformer
        );

        $this->assertTrue($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception_ignored_should_validate_other_constraints()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new UnitEnumElement(
            TestUnitEnum::class,
            new ConstraintValueValidator(
                [new Closure(function () { return 'validation error'; })],
                new TransformerExceptionConstraint(ignoreException: true)
            ),
            $transformer
        );

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('validation error', $element->error()->global());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new UnitEnumElement(TestUnitEnum::class, transformer: new ClosureTransformer(function ($value, $_, $toPhp) {
            if ($toPhp) {
                return ucfirst(strtolower($value));
            } else {
                return strtoupper($value);
            }
        }));

        $element->submit('foO')->valid();
        $this->assertSame(TestUnitEnum::Foo, $element->value());
        $this->assertSame('FOO', $element->httpValue());
    }

    #[DataProvider('provideValidValues')]
    public function test_import($value, $expected)
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertSame($expected, $element->import($value)->value());
    }

    public static function provideValidValues()
    {
        return [
            [TestUnitEnum::Foo, TestUnitEnum::Foo],
            [null, null],
        ];
    }

    #[DataProvider('provideInvalidValue')]
    public function test_import_invalid_type($value)
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('The import()\'ed value of a Bdf\Form\Leaf\UnitEnumElement must be an instance of Bdf\Form\Leaf\Fixtures\TestUnitEnum or null');
        $element = new UnitEnumElement(TestUnitEnum::class);

        $element->import($value);
    }

    /**
     *
     */
    public static function provideInvalidValue()
    {
        return [
            [[]],
            [new \stdClass()],
            [STDIN],
            ['foo'],
            [123],
            [TestBackedEnum::Foo],
        ];
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertSame('Bar', $element->import(TestUnitEnum::Bar)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $newElement = $element->setContainer($container);

        $this->assertNotSame($element, $newElement);
        $this->assertSame($container, $newElement->container());
    }

    /**
     *
     */
    public function test_root_without_container()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent($form = new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }

    /**
     *
     */
    public function test_view()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);
        $element->import(TestUnitEnum::Bar);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="text" name="name" value="Bar" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="Bar" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('Bar', $view->value());
        $this->assertEquals('name', $view->name());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertFalse($view->required());
        $this->assertEmpty($view->constraints());

        $element->import(null);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="text" name="name" value="" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="" />', (string) $view->id('foo')->class('form-element'));

        $this->assertEquals('', $view->value());

        $this->assertEquals('<input type="text" name="" value="" />', (string) $element->view());
    }

    /**
     *
     */
    public function test_view_not_submitted()
    {
        $element = new UnitEnumElement(TestUnitEnum::class);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="text" name="name" value="" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertNull($view->value());
        $this->assertEquals('name', $view->name());
        $this->assertFalse($view->hasError());
        $this->assertNull($view->error());
        $this->assertFalse($view->required());
        $this->assertEmpty($view->constraints());
    }

    /**
     *
     */
    public function test_error()
    {
        $element = (new EnumElementBuilder())->enumClass(UnitEnumElement::class)->satisfy(function() { return false; })->buildElement();
        $element->submit('ok');

        $error = $element->error(HttpFieldPath::named('foo'));

        $this->assertEquals('foo', $error->field());
        $this->assertEquals('The value is invalid', $error->global());
        $this->assertEquals('CUSTOM_ERROR', $error->code());
        $this->assertEmpty($error->children());
    }
}
