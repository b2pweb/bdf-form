<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use PHPUnit\Framework\TestCase;

class PhoneChildBuilderTest extends TestCase
{
    /**
     * @dataProvider provideFormats
     */
    public function test_saveAsString($format, $formatted)
    {
        $builder = new PhoneChildBuilder('child', new PhoneElementBuilder());
        $builder->saveAsString($format)->getter()->setter()->region('FR');

        $child = $builder->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

        $child->import(['child' => '0142563698']);

        $target = [];
        $child->fill($target);

        $this->assertSame(['child' => $formatted], $target);
    }

    /**
     *
     */
    public function test_saveAsString_with_null_should_disable_formatting()
    {
        $builder = new PhoneChildBuilder('child', new PhoneElementBuilder());
        $builder->saveAsString()->saveAsString(null)->getter()->setter()->region('FR');

        $child = $builder->buildChild();
        $child->setParent(new Form(new ChildrenCollection()));

        $child->submit(['child' => '0142563698']);

        $target = [];
        $child->fill($target);

        $this->assertInstanceOf(PhoneNumber::class, $target['child']);
    }

    public function provideFormats()
    {
        return [
            [PhoneNumberFormat::E164, '+33142563698'],
            [PhoneNumberFormat::INTERNATIONAL, '+33 1 42 56 36 98'],
            [PhoneNumberFormat::NATIONAL, '01 42 56 36 98'],
            [PhoneNumberFormat::RFC3966, 'tel:+33-1-42-56-36-98'],
        ];
    }
}
