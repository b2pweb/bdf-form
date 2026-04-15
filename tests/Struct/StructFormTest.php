<?php

namespace Bdf\Form\Struct;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Struct\Fixtures\Color;
use Bdf\Form\Struct\Fixtures\ConstraintDto;
use Bdf\Form\Struct\Fixtures\CustomDate;
use Bdf\Form\Struct\Fixtures\DtoWithDate;
use Bdf\Form\Struct\Fixtures\IntEnum;
use Bdf\Form\Struct\Fixtures\OptionalDto;
use Bdf\Form\Struct\Fixtures\Point;
use Bdf\Form\Struct\Fixtures\Shape;
use Bdf\Form\Struct\Fixtures\SimpleDto;
use Bdf\Form\Struct\Fixtures\SimpleEnum;
use Bdf\Form\Struct\Fixtures\StructWithEmbedded;
use Bdf\Form\Struct\Fixtures\StructWithEnum;
use Bdf\Form\Struct\Fixtures\StructWithOptionalEmbedded;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function random_bytes;
use function str_replace;
use function sys_get_temp_dir;

class StructFormTest extends TestCase
{
    #[Test, DataProvider('provideAttributesProcessor')]
    public function simpleDto(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(SimpleDto::class, processor: $processor);

        $form->submit([]);

        $this->assertFalse($form->valid());
        $this->assertEquals([
            'name' => 'This value should not be blank.',
            'value' => 'This value should not be blank.',
        ], $form->error()->toArray());

        $form->submit([
            'name' => 'bar',
            'value' => 42
        ]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new SimpleDto('bar', 42), $form->value());
    }

    #[Test, DataProvider('provideAttributesProcessor')]
    public function simpleDtoWithOptionalFields(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(OptionalDto::class, processor: $processor);

        $form->submit([]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new OptionalDto(null), $form->value());

        $form->submit([
            'name' => 'bar',
            'value' => 42
        ]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new OptionalDto('bar', 42), $form->value());
    }

    #[Test, DataProvider('provideAttributesProcessor')]
    public function dtoWithConstraints(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(ConstraintDto::class, processor: $processor);

        $form->submit([]);
        $this->assertFalse($form->valid());
        $this->assertEquals([
            'name' => 'This value should not be blank.',
            'value' => 'This value should not be blank.',
        ], $form->error()->toArray());

        $form->submit([
            'name' => 'b',
            'value' => -5,
        ]);
        $this->assertFalse($form->valid());
        $this->assertEquals([
            'name' => 'This value is not valid.',
            'value' => 'This value should be positive.',
        ], $form->error()->toArray());

        $form->submit([
            'name' => 'bar',
            'value' => 50000,
        ]);
        $this->assertFalse($form->valid());
        $this->assertEquals([
            'value' => 'This value should be less than 250.',
        ], $form->error()->toArray());

        $form->submit([
            'name' => '   bar   ',
            'value' => 42
        ]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new ConstraintDto('bar', 42), $form->value());
    }

    #[Test, DataProvider('provideAttributesProcessor')]
    public function withEmbedded(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(StructWithEmbedded::class, processor: $processor);

        $form->submit([]);

        $this->assertFalse($form->valid());
        $this->assertEquals([
            'id' => 'This value should not be blank.',
            'embedded' => [
                'name' => 'This value should not be blank.',
                'value' => 'This value should not be blank.',
            ],
        ], $form->error()->toArray());

        $form->submit([
            'id' => 158,
            'embedded' => [
                'name' => 'bob',
                'value' => 41,
            ],
        ]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new StructWithEmbedded(158, new SimpleDto('bob', 41)), $form->value());
    }

    #[Test, DataProvider('provideAttributesProcessor')]
    public function withOptionalEmbedded(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(StructWithOptionalEmbedded::class, processor: $processor);

        $form->submit([]);

        $this->assertFalse($form->valid());
        $this->assertEquals([
            'id' => 'This value should not be blank.',
        ], $form->error()->toArray());

        $form->submit(['id' => 745]);
        $this->assertTrue($form->valid());
        $this->assertEquals(new StructWithOptionalEmbedded(745, null), $form->value());

        $form->submit(['id' => 745, 'embedded' => ['value' => '']]);
        $this->assertFalse($form->valid());
        $this->assertEquals([
            'embedded' => [
                'name' => 'This value should not be blank.',
                'value' => 'This value should not be blank.',
            ]
        ], $form->error()->toArray());

        $form->submit([
            'id' => 158,
            'embedded' => [
                'name' => 'bob',
                'value' => 41,
            ],
        ]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new StructWithOptionalEmbedded(158, new SimpleDto('bob', 41)), $form->value());
    }

    #[Test, DataProvider('provideAttributesProcessor')]
    public function withArrayOfStruct(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(Shape::class, processor: $processor);

        $form->submit([]);

        $this->assertFalse($form->valid());
        $this->assertEquals([
            'color' => [
                'red' => 'This value should not be blank.',
                'green' => 'This value should not be blank.',
                'blue' => 'This value should not be blank.',
            ],
            'points' => 'This collection should contain 3 elements or more.',
        ], $form->error()->toArray());

        $form->submit([
            'color' => [
                'red' => 42,
                'green' => 33,
                'blue' => 0,
            ],
            'points' => [
                ['x' => 3, 'y' => 4],
                ['x' => 5, 'y' => 6],
                ['x' => 7, 'y' => 8],
            ]
        ]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new Shape(
            new Color(42, 33, 0),
            [
                new Point(3, 4),
                new Point(5, 6),
                new Point(7, 8),
            ]
        ), $form->value());
    }

    #[Test]
    public function codeGenerator()
    {
        $this->assertGenerated(
            <<<'PHP'
            namespace Generated;
            
            use Bdf\Form\Aggregate\ArrayElement;
            use Bdf\Form\Aggregate\FormBuilderInterface;
            use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
            use Bdf\Form\Attribute\Processor\PostConfigureInterface;
            use Bdf\Form\PropertyAccess\Getter;
            use Bdf\Form\PropertyAccess\Setter;
            use Bdf\Form\Struct\Fixtures\Shape;
            use Bdf\Form\Struct\StructForm;
            use Symfony\Component\Validator\Constraints\Count;
            
            class GeneratedConfigurator implements AttributesProcessorInterface
            {
                /**
                 * {@inheritdoc}
                 */
                function configureBuilder(object|string $context, FormBuilderInterface $builder): ?PostConfigureInterface
                {
                    $builder->generates(Shape::class);
            
                    $color = $builder->add('color', StructForm::class);
                    $color->hydrator(new Setter(null))->extractor(new Getter(null));
                    $color->required(null);
                    $color->class('Bdf\Form\Struct\Fixtures\Color');
            
                    $points = $builder->add('points', ArrayElement::class);
                    $points->arrayConstraint(new Count(min: 3));
                    $points->struct('Bdf\Form\Struct\Fixtures\Point');
                    $points->hydrator(new Setter(null))->extractor(new Getter(null));
            
                    return null;
                }
            }

            PHP,
            Shape::class,
        );
    }

    #[Test, DataProvider('provideAttributesProcessor')]
    public function withDate(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(DtoWithDate::class, processor: $processor);

        $form->submit([]);

        $this->assertFalse($form->valid());
        $this->assertEquals([
            'start' => 'This value should not be blank.',
            'end' => 'This value should not be blank.',
        ], $form->error()->toArray());

        $form->submit([
            'start' => '2019-01-01',
            'end' => '2019-01-31',
        ]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new DtoWithDate(
            new DateTimeImmutable('2019-01-01'),
            new CustomDate('2019-01-31'),
        ), $form->value());
    }

    #[Test, DataProvider('provideAttributesProcessor')]
    public function withEnum(AttributesProcessorInterface $processor)
    {
        $form = new StructForm(StructWithEnum::class, processor: $processor);

        $form->submit([
            'i' => 42,
            's' => 'invalid',
        ]);

        $this->assertFalse($form->valid());
        $this->assertEquals([
            'i' => 'This value should not be blank.',
            's' => 'This value should not be blank.',
        ], $form->error()->toArray());

        $form->submit([
            'i' => 2,
            's' => 'One',
        ]);

        $this->assertEquals(new StructWithEnum(
            i: IntEnum::Bar,
            s: SimpleEnum::One,
        ), $form->value());
    }

    public function assertGenerated(string $expected, string $structClass): void
    {
        $generator = new GenerateConfiguratorStrategy('Generated\GeneratedConfigurator');
        $processor = new StructAttributesProcessorFactory()->create($generator);

        $processor->configureBuilder($structClass, new FormBuilder());
        $this->assertEquals($expected, $generator->code());
    }

    /**
     * @return AttributesProcessorInterface[]
     */
    public static function provideAttributesProcessor(): array
    {
        $factory = new StructAttributesProcessorFactory();

        return [
            'reflection' => [$factory->runtime()],
            'compile' => [$factory->generated(
                fn ($form) => 'Generated\\G' . bin2hex(random_bytes(16)),
                fn ($className) => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Generated_' . str_replace('\\', '_', $className) . '.php'
            )],
        ];
    }
}
