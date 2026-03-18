<?php

namespace Tests\Form\Attribute\Form;

use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Form\Csrf;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Tests\Form\Attribute\TestCase;

class CsrfTest extends TestCase
{
    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test(AttributesProcessorInterface $processor)
    {
        $form = new #[Csrf] class(null, $processor) extends AttributeForm {
        };

        $form->submit([]);
        $this->assertFalse($form->valid());
        $this->assertEquals(['_token' => 'The CSRF token is invalid.'], $form->error()->toArray());

        $form->submit(['_token' => $form['_token']->view()->value()]);
        $this->assertTrue($form->valid());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_message(AttributesProcessorInterface $processor)
    {
        $form = new #[Csrf(message: 'my error')] class(null, $processor) extends AttributeForm {
        };

        $form->submit([]);
        $this->assertFalse($form->valid());
        $this->assertEquals(['_token' => 'my error'], $form->error()->toArray());

        $form->submit(['_token' => $form['_token']->view()->value()]);
        $this->assertTrue($form->valid());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_name(AttributesProcessorInterface $processor)
    {
        $form = new #[Csrf(name: 't')] class(null, $processor) extends AttributeForm {
        };

        $form->submit([]);
        $this->assertFalse($form->valid());
        $this->assertEquals(['t' => 'The CSRF token is invalid.'], $form->error()->toArray());

        $form->submit(['t' => $form['t']->view()->value()]);
        $this->assertTrue($form->valid());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_invalidate(AttributesProcessorInterface $processor)
    {
        $form = new #[Csrf(invalidate: true)] class(null, $processor) extends AttributeForm {
        };

        $token = $form['_token']->view()->value();

        $form->submit(['_token' => $token]);
        $this->assertTrue($form->valid());

        $form->submit(['_token' => $token]);
        $this->assertFalse($form->valid());
    }

    /**
     * @dataProvider provideAttributesProcessor
     */
    public function test_tokenId(AttributesProcessorInterface $processor)
    {
        $form = new #[Csrf(tokenId: 'my_token_id')] class(null, $processor) extends AttributeForm {
        };

        $token = $form['_token']->view()->value();

        $this->assertSame('my_token_id', $token->getId());
    }

    /**
     * @return void
     */
    public function test_code_generator()
    {
        $form = new #[Csrf(tokenId: 'my_token', message: 'my error', invalidate: true)] class extends AttributeForm {
        };

        $this->assertGenerated(<<<'PHP'
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;

class GeneratedConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->csrf('_token')->tokenId('my_token')->message('my error')->invalidate(true);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
    }
}

PHP
            , $form);
    }
}
