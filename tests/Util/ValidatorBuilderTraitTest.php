<?php

namespace Bdf\Form\Util;

use Bdf\Form\Constraint\Closure;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Registry\Registry;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Positive;

class ValidatorBuilderTraitTest extends TestCase
{
    /**
     * @var StringElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new class extends StringElementBuilder {
            public function constraintsProvider(callable $provider)
            {
                $this->addConstraintsProvider($provider);
            }
        };
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new NotEqualTo('hello'))->buildElement();

        $this->assertFalse($element->submit('hello')->valid());
        $this->assertTrue($element->submit('world')->valid());
    }

    /**
     *
     */
    public function test_satisfy_order()
    {
        $this->builder->satisfy(function () { return 'error 1'; });
        $this->builder->satisfy(function () { return 'error 2'; });
        $element = $this->builder->buildElement();

        $this->assertFalse($element->submit(null)->valid());
        $this->assertEquals('error 1', $element->error()->global());

        $this->builder->satisfy(function () { return 'error 3'; }, null, false);
        $element = $this->builder->buildElement();

        $this->assertFalse($element->submit(null)->valid());
        $this->assertEquals('error 3', $element->error()->global());
    }

    /**
     *
     */
    public function test_satisfy_with_className_and_options()
    {
        $element = $this->builder->satisfy(NotEqualTo::class, ['value' => 'hello'])->buildElement();

        $this->assertFalse($element->submit('hello')->valid());
        $this->assertTrue($element->submit('world')->valid());
    }

    /**
     *
     */
    public function test_required()
    {
        $element = $this->builder->required()->buildElement();

        $element->submit(null);
        $this->assertEquals('This value should not be blank.', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $element = $this->builder->required('my message')->buildElement();

        $element->submit(null);
        $this->assertEquals('my message', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_constraint()
    {
        $element = $this->builder->required(new Positive())->buildElement();

        $element->submit('-1');
        $this->assertEquals('This value should be positive.', $element->error()->global());
    }

    /**
     *
     */
    public function test_constraintsProvider()
    {
        $constraints = [];

        $this->builder->constraintsProvider(function($registry) use(&$constraints) {
            $this->assertInstanceOf(Registry::class, $registry);

            return [
                new Closure(function () use(&$constraints) {
                    $constraints[] = 'A';
                    return true;
                }),
                new Closure(function () use(&$constraints) {
                    $constraints[] = 'B';
                    return true;
                }),
            ];
        });
        $this->builder->constraintsProvider(function() use(&$constraints) {
            return [
                new Closure(function () use(&$constraints) {
                    $constraints[] = 'C';
                    return true;
                }),
            ];
        });

        $element = $this->builder->buildElement();
        $element->submit('foo');

        $this->assertSame(['A', 'B', 'C'], $constraints);
    }

    /**
     *
     */
    public function test_ignoreTransformerException()
    {
        $this->builder->transformer(function () { throw new \Exception('my error'); });

        $element = $this->builder->ignoreTransformerException()->buildElement();
        $element->submit('foo');

        $this->assertTrue($element->valid());

        $element = $this->builder->ignoreTransformerException(false)->buildElement();
        $element->submit('foo');

        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('my error', $element->error()->global());
        $this->assertEquals('TRANSFORM_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_transformerErrorMessage()
    {
        $this->builder->transformer(function () { throw new \Exception('my error'); });

        $element = $this->builder->transformerErrorMessage('custom message')->buildElement();
        $element->submit('foo');

        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('custom message', $element->error()->global());
        $this->assertEquals('TRANSFORM_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_transformerErrorCode()
    {
        $this->builder->transformer(function () { throw new \Exception('my error'); });

        $element = $this->builder->transformerErrorCode('CUSTOM_ERROR')->buildElement();
        $element->submit('foo');

        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertEquals('my error', $element->error()->global());
        $this->assertEquals('CUSTOM_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_transformerExceptionValidation()
    {
        $this->builder->transformer(function () { throw new \Exception('my error'); });

        $element = $this->builder->transformerExceptionValidation(function($value, $constraint, $element) use(&$arguments) {
            $arguments = func_get_args();

            $constraint->message = 'foo message';
            $constraint->code = 'FOO_ERROR';

            return $value !== 'foo';
        })->buildElement();

        $element->submit('foo');

        $this->assertSame('foo', $arguments[0]);
        $this->assertInstanceOf(TransformerExceptionConstraint::class, $arguments[1]);
        $this->assertSame($element, $arguments[2]);

        $this->assertTrue($element->valid());

        $element->submit('bar');
        $this->assertEquals('foo message', $element->error()->global());
        $this->assertEquals('FOO_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_defaultTransformerExceptionConstraintOptions()
    {
        $builder = new class extends StringElementBuilder {
            protected function defaultTransformerExceptionConstraintOptions(): array
            {
                return ['message' => 'my error message', 'code' => 'MY_ERROR'];
            }
        };

        $element = $builder->transformer(function () { throw new \Exception('my error'); })->buildElement();

        $element->submit('bar');
        $this->assertEquals('my error message', $element->error()->global());
        $this->assertEquals('MY_ERROR', $element->error()->code());
    }
}
