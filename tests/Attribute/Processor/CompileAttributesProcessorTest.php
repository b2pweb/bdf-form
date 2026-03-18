<?php

namespace Tests\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Child\GetSet;
use Bdf\Form\Attribute\Constraint\CallbackConstraint;
use Bdf\Form\Attribute\Form\Generates;
use Bdf\Form\Attribute\Processor\CompileAttributesProcessor;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class CompileAttributesProcessorTest extends TestCase
{
    public function test_compile_and_write_file_if_not_exists_and_load_class()
    {
        if (file_exists('/tmp/Generated/Tests/Form/Attribute/Processor/MyFormConfigurator.php')) {
            unlink('/tmp/Generated/Tests/Form/Attribute/Processor/MyFormConfigurator.php');
        }

        if (is_dir('/tmp/Generated/Tests/Form/Attribute/Processor')) {
            rmdir('/tmp/Generated/Tests/Form/Attribute/Processor');
            rmdir('/tmp/Generated/Tests/Form/Attribute');
        }

        clearstatcache();

        $processor = new CompileAttributesProcessor(
            fn (AttributeForm $form) => 'Generated\\' . get_class($form) . 'Configurator',
            fn (string $className) => '/tmp' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php'
        );

        $form = new MyForm();

        $postConfigure = $processor->configureBuilder($form, new FormBuilder());

        $this->assertFileExists('/tmp/Generated/Tests/Form/Attribute/Processor/MyFormConfigurator.php');
        $this->assertStringEqualsFile(
            '/tmp/Generated/Tests/Form/Attribute/Processor/MyFormConfigurator.php',
<<<'PHP'
<?php
namespace Generated\Tests\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Constraint\Closure as ClosureConstraint;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Tests\Form\Attribute\Processor\MyForm;
use Tests\Form\Attribute\Processor\Person;

class MyFormConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(Person::class);

        $firstName = $builder->add('firstName', StringElement::class);
        $firstName->satisfy(new NotBlank());
        $firstName->satisfy(new ClosureConstraint([$form, 'validateName']));
        $firstName->hydrator(new Setter(null))->extractor(new Getter(null));

        $lastName = $builder->add('lastName', StringElement::class);
        $lastName->satisfy(new ClosureConstraint([$form, 'validateName']));
        $lastName->hydrator(new Setter(null))->extractor(new Getter(null));

        $age = $builder->add('age', IntegerElement::class);
        $age->satisfy(new Positive());
        $age->satisfy(new LessThan(150));
        $age->hydrator(new Setter(null))->extractor(new Getter(null));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        (\Closure::bind(function () use ($inner, $form) {
            $form->firstName = $inner['firstName']->element();
            $form->lastName = $inner['lastName']->element();
            $form->age = $inner['age']->element();
        }, null, MyForm::class))();
    }
}

PHP
        );

        $this->assertTrue(class_exists('Generated\Tests\Form\Attribute\Processor\MyFormConfigurator', false));
        $this->assertInstanceOf('Generated\Tests\Form\Attribute\Processor\MyFormConfigurator', $postConfigure);
    }

    public function test_file_already_exists_should_only_be_included()
    {
        $file = '/tmp/generated_configurator.php';

        file_put_contents(
            $file,
$code = <<<'PHP'
<?php
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;

class Configurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
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

        );

        $processor = new CompileAttributesProcessor(
            fn (AttributeForm $form) => 'Generated\\Configurator',
            fn (string $className) => $file
        );

        $form = new MyForm();

        $postConfigure = $processor->configureBuilder($form, new FormBuilder());

        $this->assertStringEqualsFile($file, $code);

        $this->assertTrue(class_exists('Generated\Configurator', false));
        $this->assertInstanceOf('Generated\Configurator', $postConfigure);
    }

    public function test_file_already_exists_but_with_invalid_class_should_throw_error()
    {
        $file = '/tmp/invalid_class_configurator.php';

        file_put_contents(
            $file,
$code = <<<'PHP'
<?php
namespace Generated;

class InvalidConfigurator
{
}
PHP

        );

        $processor = new CompileAttributesProcessor(
            fn (AttributeForm $form) => 'Generated\\InvalidConfigurator',
            fn (string $className) => $file
        );

        $form = new MyForm();

        try {
            $processor->configureBuilder($form, new FormBuilder());
            $this->fail('expect LogicException');
        } catch (\LogicException $e) {
            $this->assertEquals('Invalid generated class "Generated\InvalidConfigurator" in file "/tmp/invalid_class_configurator.php"', $e->getMessage());
        }

        $this->assertStringEqualsFile($file, $code);
    }

    public function test_file_already_exists_but_without_class_on_file()
    {
        $file = '/tmp/invalid_class_configurator.php';

        file_put_contents(
            $file,
$code = <<<'PHP'
<?php

return false;
PHP

        );

        $processor = new CompileAttributesProcessor(
            fn (AttributeForm $form) => 'Generated\\NotAClass',
            fn (string $className) => $file
        );

        $form = new MyForm();

        try {
            $processor->configureBuilder($form, new FormBuilder());
            $this->fail('expect LogicException');
        } catch (\LogicException $e) {
            $this->assertEquals('Invalid generated class "Generated\NotAClass" in file "/tmp/invalid_class_configurator.php"', $e->getMessage());
        }

        $this->assertStringEqualsFile($file, $code);
    }

    public function test_generate()
    {
        $filename = '/tmp/manual_generated_configurator.php';

        file_put_contents($filename, 'invalid php file');

        $processor = new CompileAttributesProcessor(
            fn (AttributeForm $form) => 'Generated\ManualConfigurator',
            fn (string $className) => $filename
        );

        $form = new MyForm();

        $processor->generate($form);

        $this->assertFileExists($filename);
        $this->assertStringEqualsFile(
            $filename,
            <<<'PHP'
<?php
namespace Generated;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\AttributesProcessorInterface;
use Bdf\Form\Attribute\Processor\PostConfigureInterface;
use Bdf\Form\Constraint\Closure as ClosureConstraint;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\PropertyAccess\Getter;
use Bdf\Form\PropertyAccess\Setter;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Tests\Form\Attribute\Processor\MyForm;
use Tests\Form\Attribute\Processor\Person;

class ManualConfigurator implements AttributesProcessorInterface, PostConfigureInterface
{
    /**
     * {@inheritdoc}
     */
    function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $builder->generates(Person::class);

        $firstName = $builder->add('firstName', StringElement::class);
        $firstName->satisfy(new NotBlank());
        $firstName->satisfy(new ClosureConstraint([$form, 'validateName']));
        $firstName->hydrator(new Setter(null))->extractor(new Getter(null));

        $lastName = $builder->add('lastName', StringElement::class);
        $lastName->satisfy(new ClosureConstraint([$form, 'validateName']));
        $lastName->hydrator(new Setter(null))->extractor(new Getter(null));

        $age = $builder->add('age', IntegerElement::class);
        $age->satisfy(new Positive());
        $age->satisfy(new LessThan(150));
        $age->hydrator(new Setter(null))->extractor(new Getter(null));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function postConfigure(AttributeForm $form, FormInterface $inner): void
    {
        (\Closure::bind(function () use ($inner, $form) {
            $form->firstName = $inner['firstName']->element();
            $form->lastName = $inner['lastName']->element();
            $form->age = $inner['age']->element();
        }, null, MyForm::class))();
    }
}

PHP
        );

        $this->assertFalse(class_exists('Generated\ManualConfigurator', false));
    }
}

#[Generates(Person::class)]
class MyForm extends AttributeForm
{
    #[NotBlank, CallbackConstraint('validateName'), GetSet]
    private StringElement $firstName;

    #[CallbackConstraint('validateName'), GetSet]
    private StringElement $lastName;

    #[Positive, LessThan(150), GetSet]
    private IntegerElement $age;

    public function validateName(?string $value, StringElement $input): bool
    {
        if (!$value) {
            return true;
        }

        return preg_match('#[a-z][a-z -]*#i', $value);
    }
}

class Person
{
    public function __construct(
        public string $firstName,
        public ?string $lastName = null,
        public ?int $age = null,
    ) {
    }
}
