<?php

namespace Bdf\Form\Csrf;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Custom\CustomForm;
use Bdf\Form\Leaf\LeafRootElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class CsrfElementTest
 */
class CsrfElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new CsrfElement();

        $this->assertFalse($element->valid());
        $this->assertTrue($element->error()->empty());
        $this->assertInstanceOf(CsrfToken::class, $element->value());
        $this->assertEquals(CsrfElement::class, $element->value()->getId());
        $this->assertSame($element->value()->getValue(), $element->httpValue());
    }

    /**
     *
     */
    public function test_submit_invalid()
    {
        $element = new CsrfElement();

        $this->assertFalse($element->submit('invalid')->valid());
        $this->assertEquals(new CsrfToken(CsrfElement::class, 'invalid'), $element->value());
        $this->assertEquals('The CSRF token is invalid.', $element->error()->global());
        $this->assertEquals('INVALID_TOKEN_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new CsrfElement();

        $this->assertFalse($element->submit(null)->valid());
        $this->assertEquals(new CsrfToken(CsrfElement::class, null), $element->value());
        $this->assertEquals('The CSRF token is invalid.', $element->error()->global());
        $this->assertEquals('INVALID_TOKEN_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_submit_valid()
    {
        $element = new CsrfElement();

        $value = $element->value()->getValue();

        $this->assertTrue($element->submit($value)->valid());
        $this->assertEquals(new CsrfToken(CsrfElement::class, $value), $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_patch_invalid()
    {
        $element = new CsrfElement();
        $element->submit($element->httpValue());

        $this->assertFalse($element->submit('invalid')->valid());
        $this->assertEquals(new CsrfToken(CsrfElement::class, 'invalid'), $element->value());
        $this->assertEquals('The CSRF token is invalid.', $element->error()->global());
        $this->assertEquals('INVALID_TOKEN_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_patch_null()
    {
        $element = new CsrfElement();
        $element->submit($element->httpValue());

        $this->assertFalse($element->submit(null)->valid());
        $this->assertEquals(new CsrfToken(CsrfElement::class, null), $element->value());
        $this->assertEquals('The CSRF token is invalid.', $element->error()->global());
        $this->assertEquals('INVALID_TOKEN_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_patch_valid()
    {
        $element = new CsrfElement();
        $element->submit($element->httpValue());

        $value = $element->value()->getValue();

        $this->assertTrue($element->submit($value)->valid());
        $this->assertEquals(new CsrfToken(CsrfElement::class, $value), $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_httpValue()
    {
        $element = new CsrfElement();

        $this->assertSame($element->value()->getValue(), $element->httpValue());
        $element->submit('token');
        $this->assertSame('token', $element->httpValue());
    }
    /**
     *
     */
    public function test_container()
    {
        $element = new CsrfElement();

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
        $element = new CsrfElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new CsrfElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent($form = new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }

    /**
     *
     */
    public function test_invalidateToken()
    {
        $element = new CsrfElement();
        $value = $element->value()->getValue();
        $element->submit($value);

        $this->assertTrue($element->valid());

        $element->invalidateToken();
        $this->assertFalse($element->submit($value)->valid());
    }

    /**
     *
     */
    public function test_import()
    {
        $this->expectException(\BadMethodCallException::class);

        (new CsrfElement())->import('test');
    }

    /**
     *
     */
    public function test_view()
    {
        $element = new CsrfElement();

        $view = $element->view(HttpFieldPath::named('token'));
        $token = $view->value();

        $this->assertEquals(CsrfElement::class, $view->type());
        $this->assertEquals('<input type="hidden" name="token" value="'.$token.'" required />', (string) $view);
        $this->assertEquals('token', $view->name());
        $this->assertFalse($view->hasError());

        $element->submit('invalid');

        $view = $element->view(HttpFieldPath::named('token'));
        $token = $view->value();

        $this->assertEquals('<input type="hidden" name="token" value="'.$token.'" required />', (string) $view);
        $this->assertTrue($view->hasError());
        $this->assertEquals('The CSRF token is invalid.', $view->error());

        $this->assertTrue($element->submit($token)->valid());
    }

    /**
     *
     */
    public function test_error()
    {
        $element = new CsrfElement();
        $element->submit('ok');

        $error = $element->error(HttpFieldPath::named('foo'));

        $this->assertEquals('foo', $error->field());
        $this->assertEquals('The CSRF token is invalid.', $error->global());
        $this->assertEquals('INVALID_TOKEN_ERROR', $error->code());
        $this->assertEmpty($error->children());
    }

    public function test_disable_validation_flag()
    {
        $form = new class extends CustomForm {
            public function configure(FormBuilderInterface $builder): void
            {
                $builder->string('foo')->getter()->setter();
                $builder->csrf();
            }
        };

        $form->submit(['foo' => 'bar']);
        $this->assertFalse($form->valid());
        $this->assertEquals(['_token' => 'The CSRF token is invalid.'], $form->error()->toArray());

        $form->root()->set(CsrfValueValidator::FLAG_DISABLE_CSRF_VALIDATION, true);

        $form->submit(['foo' => 'bar']);
        $this->assertTrue($form->valid());
    }

    public function test_token_validation_on_embedded()
    {
        $form = new class extends CustomForm {
            public function configure(FormBuilderInterface $builder): void
            {
                $builder->string('foo')->getter()->setter();
                $builder->embedded('embedded', function ($builder) {
                    $builder->string('bar')->getter()->setter();
                    $builder->csrf()->validateOnSubForms();
                });
            }
        };

        $form->submit(['foo' => 'a', 'embedded' => ['bar' => 'b']]);
        $this->assertFalse($form->valid());
        $this->assertEquals(['embedded' => ['_token' => 'The CSRF token is invalid.']], $form->error()->toArray());

        $form = new class extends CustomForm {
            public function configure(FormBuilderInterface $builder): void
            {
                $builder->string('foo')->getter()->setter();
                $builder->embedded('embedded', function ($builder) {
                    $builder->string('bar')->getter()->setter();
                    $builder->csrf();
                });
            }
        };

        $form->submit(['foo' => 'a', 'embedded' => ['bar' => 'b']]);
        $this->assertTrue($form->valid());
    }
}
