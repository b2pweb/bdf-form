<?php

namespace Bdf\Form\Csrf;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * Class CsrfElementBuilderTest
 */
class CsrfElementBuilderTest extends TestCase
{
    /**
     * @var CsrfElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new CsrfElementBuilder();

        $firstParameter = (new \ReflectionClass(SessionTokenStorage::class))->getConstructor()->getParameters()[0];

        if ($firstParameter->getType() && $firstParameter->getType()->getName()  === RequestStack::class) {
            $stack = new RequestStack();
            $request = Request::create('http://127.0.0.1');
            $request->setSession(new Session(new MockArraySessionStorage()));
            $stack->push($request);
            $storage = new SessionTokenStorage($stack);
        } else {
            $storage = new SessionTokenStorage(new Session(new MockArraySessionStorage()));
        }

        $this->builder->tokenManager(new CsrfTokenManager(null, $storage));
    }

    /**
     *
     */
    public function test_buildElement_default()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(CsrfElement::class, $element);
        $this->assertEquals(CsrfElement::class, $element->value()->getId());
    }

    /**
     *
     */
    public function test_buildElement_message()
    {
        $element = $this->builder->message('my error')->buildElement();

        $this->assertEquals('my error', $element->submit(null)->error()->global());
    }

    /**
     *
     */
    public function test_buildElement_tokenId()
    {
        $element = $this->builder->tokenId('token')->buildElement();

        $this->assertEquals('token', $element->value()->getId());
    }

    /**
     *
     */
    public function test_invalidate()
    {
        $element = $this->builder->invalidate()->buildElement();

        $value = $element->value()->getValue();

        $this->assertTrue($element->submit($value)->valid());
        $this->assertFalse($element->failed());
        $this->assertFalse($element->submit($value)->valid());
        $this->assertTrue($element->failed());
    }

    /**
     *
     */
    public function test_tokenManager()
    {
        $manager = $this->createMock(CsrfTokenManagerInterface::class);
        $element = $this->builder->tokenManager($manager)->buildElement();

        $this->assertSame($manager, $element->getTokenManager());
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->builder->satisfy(null);
    }

    /**
     *
     */
    public function test_transformer()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->builder->transformer(null);
    }

    /**
     *
     */
    public function test_value()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->builder->value(null);
    }
}
