<?php

namespace Bdf\Form\Button;

use PHPUnit\Framework\TestCase;

/**
 * Class SubmitButtonBuilderTest
 */
class SubmitButtonBuilderTest extends TestCase
{
    /**
     *
     */
    public function test_build()
    {
        $builder = new SubmitButtonBuilder('btn');

        $button = $builder->buildButton();

        $this->assertInstanceOf(SubmitButton::class, $button);
        $this->assertEquals('btn', $button->name());
        $this->assertEquals([], $button->constraintGroups());

        $button->submit(['btn' => 'ok']);
        $this->assertTrue($button->clicked());
    }

    /**
     *
     */
    public function test_build_with_custom_button_class()
    {
        $builder = new SubmitButtonBuilder('btn', CustomButton::class);

        $this->assertInstanceOf(CustomButton::class, $builder->buildButton());
    }

    /**
     *
     */
    public function test_value()
    {
        $builder = new SubmitButtonBuilder('btn');

        $button = $builder->value('val')->buildButton();

        $button->submit(['btn' => 'ok']);
        $this->assertFalse($button->clicked());

        $button->submit(['btn' => 'val']);
        $this->assertTrue($button->clicked());
    }

    /**
     *
     */
    public function test_groups()
    {
        $builder = new SubmitButtonBuilder('btn');

        $button = $builder->groups(['grp'])->buildButton();

        $this->assertEquals(['grp'], $button->constraintGroups());
    }
}

class CustomButton implements ButtonInterface
{
    public function name(): string
    {
    }

    public function clicked(): bool
    {
    }

    public function submit($data): bool
    {
    }

    public function constraintGroups(): array
    {
    }
}
