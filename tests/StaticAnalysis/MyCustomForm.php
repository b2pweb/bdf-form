<?php

namespace Bdf\Form\StaticAnalysis;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;

/**
 * @extends CustomForm<MyGeneratedEntity>
 */
class MyCustomForm extends CustomForm
{
    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->generates(MyGeneratedEntity::class);

        $builder->string('foo')->length(['min' => 1])->getter()->setter();
        $builder->string('bar')->required()->getter()->setter();
    }

    public static function test()
    {
        $form = new MyCustomForm();
        $form->submit(['foo' => 'a', 'bar' => 'b']);
        self::check($form->value());
    }

    public static function check(?MyGeneratedEntity $entity): void
    {

    }
}

class MyGeneratedEntity
{
    /**
     * @var string
     */
    public $foo;

    /**
     * @var string
     */
    public $bar;
}
