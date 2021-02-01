<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\View\ElementViewInterface;
use PHPUnit\Framework\TestCase;

class LeafElementTest extends TestCase
{
    /**
     *
     */
    public function test_protected()
    {
        $element = new class extends LeafElement {
            protected function toPhp($httpValue)
            {
                return 'toPhp';
            }

            protected function toHttp($phpValue)
            {
                return 'toHttp';
            }

            protected function sanitize($rawValue)
            {
                return 'sanitize';
            }

            protected function choiceView(): ?array
            {
                return [];
            }

            public function view(?HttpFieldPath $field = null): ElementViewInterface
            {
                $view = parent::view($field);

                $view->foo('bar');

                return $view;
            }

            /**
             *
             */
            public function test()
            {
                TestCase::assertSame('toPhp', $this->toPhp(''));
                TestCase::assertSame('toHttp', $this->toHttp(''));
                TestCase::assertSame('sanitize', $this->sanitize(''));
                TestCase::assertSame([], $this->choiceView());

                $view = $this->view();
                TestCase::assertSame(['foo' => 'bar'], $view->attributes());
            }
        };

        $element->test();
    }
}
