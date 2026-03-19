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

class BackedEnumElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new BackedEnumElement(TestBackedEnum::class);

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
        $element = new BackedEnumElement(TestBackedEnum::class);

        $this->assertTrue($element->submit('foo')->valid());
        $this->assertFalse($element->failed());
        $this->assertSame(TestBackedEnum::Foo, $element->value());
        $this->assertTrue($element->error()->empty());

        $this->assertTrue($element->submit(TestBackedEnum::Bar)->valid());
        $this->assertFalse($element->failed());
        $this->assertSame(TestBackedEnum::Bar, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    #[
        TestWith([false]),
        TestWith([new stdClass()]),
        TestWith([TestUnitEnum::Bar]),
        TestWith(['invalid']),
        TestWith([42]),
        TestWith([42.1]),
    ]
    public function test_submit_invalid(mixed $value)
    {
        $element = new BackedEnumElement(TestBackedEnum::class);

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
        $element = new BackedEnumElement(TestBackedEnum::class);

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new BackedEnumElement(TestBackedEnum::class, new ConstraintValueValidator([new NotBlank()]));

        $this->assertFalse($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertEquals('This value should not be blank.', $element->error()->global());

        $this->assertTrue($element->submit('foo')->valid());
        $this->assertSame(TestBackedEnum::Foo, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new BackedEnumElement(TestBackedEnum::class, transformer: $transformer);

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
        $element = new BackedEnumElement(
            TestBackedEnum::class,
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
        $element = new BackedEnumElement(
            TestBackedEnum::class,
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
        $element = new BackedEnumElement(TestBackedEnum::class, transformer: new ClosureTransformer(function ($value, $_, $toPhp) {
            if ($toPhp) {
                return strtolower($value);
            } else {
                return strtoupper($value);
            }
        }));

        $element->submit('foO')->valid();
        $this->assertSame(TestBackedEnum::Foo, $element->value());
        $this->assertSame('FOO', $element->httpValue());
    }

    #[DataProvider('provideValidValues')]
    public function test_import($value, $expected)
    {
        $element = new BackedEnumElement(TestBackedEnum::class);

        $this->assertSame($expected, $element->import($value)->value());
    }

    public static function provideValidValues()
    {
        return [
            [TestBackedEnum::Foo, TestBackedEnum::Foo],
            [null, null],
        ];
    }

    #[DataProvider('provideInvalidValue')]
    public function test_import_invalid_type($value)
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('The import()\'ed value of a Bdf\Form\Leaf\BackedEnumElement must be an instance of Bdf\Form\Leaf\Fixtures\TestBackedEnum or null');
        $element = new BackedEnumElement(TestBackedEnum::class);

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
            [TestUnitEnum::Foo],
        ];
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new BackedEnumElement(TestBackedEnum::class);

        $this->assertSame('bar', $element->import(TestBackedEnum::Bar)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new BackedEnumElement(TestBackedEnum::class);

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
        $element = new BackedEnumElement(TestBackedEnum::class);

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new BackedEnumElement(TestBackedEnum::class);

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
        $element = new BackedEnumElement(TestBackedEnum::class);
        $element->import(TestBackedEnum::Bar);

        $view = $element->view(HttpFieldPath::named('name'));

        $this->assertEquals('<input type="text" name="name" value="bar" />', (string) $view);
        $this->assertEquals('<input id="foo" class="form-element" type="text" name="name" value="bar" />', (string) $view->id('foo')->class('form-element'));
        $this->assertNull($view->onError('my error'));

        $this->assertEquals('bar', $view->value());
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
        $element = new BackedEnumElement(TestBackedEnum::class);

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
        $element = (new EnumElementBuilder())->enumClass(BackedEnumElement::class)->satisfy(function() { return false; })->buildElement();
        $element->submit('ok');

        $error = $element->error(HttpFieldPath::named('foo'));

        $this->assertEquals('foo', $error->field());
        $this->assertEquals('The value is invalid', $error->global());
        $this->assertEquals('CUSTOM_ERROR', $error->code());
        $this->assertEmpty($error->children());
    }
}
