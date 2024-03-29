<?php

namespace Bdf\Form\Registry;

use Bdf\Form\Aggregate\ArrayChildBuilder;
use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Button\SubmitButtonBuilder;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Constraint\Closure;
use Bdf\Form\Csrf\CsrfElement;
use Bdf\Form\Csrf\CsrfElementBuilder;
use Bdf\Form\Custom\CustomForm;
use Bdf\Form\Custom\CustomFormBuilder;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Filter\ClosureFilter;
use Bdf\Form\Filter\TrimFilter;
use Bdf\Form\Leaf\AnyElement;
use Bdf\Form\Leaf\AnyElementBuilder;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\BooleanElementBuilder;
use Bdf\Form\Leaf\Date\DateTimeChildBuilder;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\Date\DateTimeElementBuilder;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\FloatElementBuilder;
use Bdf\Form\Leaf\Helper\EmailElement;
use Bdf\Form\Leaf\Helper\EmailElementBuilder;
use Bdf\Form\Leaf\Helper\UrlElement;
use Bdf\Form\Leaf\Helper\UrlElementBuilder;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\LeafElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Phone\PhoneChildBuilder;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Form\Phone\PhoneElementBuilder;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\DataTransformerAdapter;
use Bdf\Form\Transformer\TransformerAggregate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class RegistryTest
 */
class RegistryTest extends TestCase
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->registry = new Registry();
    }

    /**
     *
     */
    public function test_filter_with_instance_of_filter()
    {
        $filter = new TrimFilter();

        $this->assertSame($filter, $this->registry->filter($filter));
    }

    /**
     *
     */
    public function test_filter_with_classname()
    {
        $this->assertInstanceOf(TrimFilter::class, $this->registry->filter(TrimFilter::class));
    }

    /**
     *
     */
    public function test_filter_with_callback()
    {
        $this->assertInstanceOf(ClosureFilter::class, $this->registry->filter(function () {}));
    }

    /**
     *
     */
    public function test_constraint_with_constraint_instance()
    {
        $constraint = new NotBlank();

        $this->assertSame($constraint, $this->registry->constraint($constraint));
    }

    /**
     *
     */
    public function test_constraint_with_classname()
    {
        $this->assertInstanceOf(NotBlank::class, $this->registry->constraint(NotBlank::class));
    }

    /**
     *
     */
    public function test_constraint_with_classname_and_options()
    {
        $this->assertEquals(new NotBlank(['message' => 'error']), $this->registry->constraint([NotBlank::class, 'error']));
        $this->assertEquals(new NotBlank(['message' => 'error']), $this->registry->constraint([NotBlank::class, ['message' => 'error']]));
    }

    /**
     *
     */
    public function test_constraint_with_callback()
    {
        $this->assertInstanceOf(Closure::class, $this->registry->constraint(function () {}));
    }

    /**
     *
     */
    public function test_transformer_with_instance()
    {
        $transformer = new TransformerAggregate([]);

        $this->assertSame($transformer, $this->registry->transformer($transformer));
    }

    /**
     *
     */
    public function test_transformer_with_symfony_DataTransformer()
    {
        $this->assertInstanceOf(DataTransformerAdapter::class, $this->registry->transformer(new IntegerToLocalizedStringTransformer()));
    }

    /**
     *
     */
    public function test_transformer_with_callable()
    {
        $this->assertInstanceOf(ClosureTransformer::class, $this->registry->transformer(function () {}));
    }

    /**
     *
     */
    public function test_transformer_invalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid view transformer given for input \'foo\'');

        $this->registry->transformer('foo');
    }

    /**
     *
     */
    public function test_childBuilder()
    {
        $builder = $this->registry->childBuilder(StringElement::class, 'child');

        $this->assertInstanceOf(ChildBuilder::class, $builder);

        $child = $builder->buildChild();
        $this->assertInstanceOf(Child::class, $child);
        $this->assertEquals('child', $child->name());
        $this->assertInstanceOf(StringElement::class, $child->element());

        $this->assertInstanceOf(DateTimeChildBuilder::class, $this->registry->childBuilder(DateTimeElement::class, 'child'));
        $this->assertInstanceOf(PhoneChildBuilder::class, $this->registry->childBuilder(PhoneElement::class, 'child'));
        $this->assertInstanceOf(ArrayChildBuilder::class, $this->registry->childBuilder(ArrayElement::class, 'child'));
    }

    /**
     *
     */
    public function test_elementBuilder()
    {
        $this->assertInstanceOf(StringElementBuilder::class, $this->registry->elementBuilder(StringElement::class));
        $this->assertInstanceOf(IntegerElementBuilder::class, $this->registry->elementBuilder(IntegerElement::class));
        $this->assertInstanceOf(FloatElementBuilder::class, $this->registry->elementBuilder(FloatElement::class));
        $this->assertInstanceOf(BooleanElementBuilder::class, $this->registry->elementBuilder(BooleanElement::class));
        $this->assertInstanceOf(AnyElementBuilder::class, $this->registry->elementBuilder(AnyElement::class));
        $this->assertInstanceOf(CsrfElementBuilder::class, $this->registry->elementBuilder(CsrfElement::class));
        $this->assertInstanceOf(PhoneElementBuilder::class, $this->registry->elementBuilder(PhoneElement::class));
        $this->assertInstanceOf(DateTimeElementBuilder::class, $this->registry->elementBuilder(DateTimeElement::class));
        $this->assertInstanceOf(EmailElementBuilder::class, $this->registry->elementBuilder(EmailElement::class));
        $this->assertInstanceOf(UrlElementBuilder::class, $this->registry->elementBuilder(UrlElement::class));
        $this->assertInstanceOf(FormBuilder::class, $this->registry->elementBuilder(Form::class));
        $this->assertInstanceOf(ArrayElementBuilder::class, $this->registry->elementBuilder(ArrayElement::class));
        $this->assertInstanceOf(CustomFormBuilder::class, $this->registry->elementBuilder(MyCustomForm::class));
        $this->assertInstanceOf(MyCustomForm::class, $this->registry->elementBuilder(MyCustomForm::class)->buildElement());

        $builder = $this->createMock(ElementBuilderInterface::class);
        $childBuilder = $this->createMock(ChildBuilderInterface::class);
        $this->registry->register(MyCustomTestElement::class, function () use($builder) { return $builder; }, function () use ($childBuilder) { return $childBuilder; });

        $this->assertSame($builder, $this->registry->elementBuilder(MyCustomTestElement::class));
        $this->assertSame($childBuilder, $this->registry->childBuilder(MyCustomTestElement::class, 'child'));
    }

    /**
     *
     */
    public function test_elementBuilder_should_priorize_first_registered_element()
    {
        $builder1 = $this->createMock(ElementBuilderInterface::class);
        $builder2 = $this->createMock(ElementBuilderInterface::class);

        $this->registry->register(LeafElement::class, function () use($builder1) { return $builder1; });
        $this->registry->register(ElementInterface::class, function () use($builder2) { return $builder2; });

        $this->assertSame($builder1, $this->registry->elementBuilder(MyCustomTestElement::class));
    }

    /**
     *
     */
    public function test_elementBuilder_not_found()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The element '.MyCustomTestElement::class.' is not registered');

        $this->registry->elementBuilder(MyCustomTestElement::class);
    }

    /**
     *
     */
    public function test_buttonBuilder()
    {
        $this->assertInstanceOf(SubmitButtonBuilder::class, $this->registry->buttonBuilder('btn'));
        $this->assertEquals('btn', $this->registry->buttonBuilder('btn')->buildButton()->name());
    }
}

class MyCustomTestElement extends LeafElement
{
    protected function toPhp($httpValue)
    {
        return $httpValue;
    }

    protected function toHttp($phpValue)
    {
        return $phpValue;
    }
}

class MyCustomForm extends CustomForm
{
    protected function configure(FormBuilderInterface $builder): void
    {
    }
}
