# BDF Form

Library for handle form, and request validation.

[![build](https://github.com/b2pweb/bdf-form/actions/workflows/php.yml/badge.svg)](https://github.com/b2pweb/bdf-form/actions/workflows/php.yml)
[![codecov](https://codecov.io/github/b2pweb/bdf-form/branch/master/graph/badge.svg?token=VOFSPEWYKX)](https://app.codecov.io/github/b2pweb/bdf-form)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-form.svg)](https://packagist.org/packages/b2pweb/bdf-form)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-form.svg)](https://packagist.org/packages/b2pweb/bdf-form)
[![Type Coverage](https://shepherd.dev/github/b2pweb/bdf-form/coverage.svg)](https://shepherd.dev/github/b2pweb/bdf-form)

## Table of content

- [Installation using composer](#installation-using-composer)
- [Basic usage](#basic-usage)
- [Handle entities](#handle-entities)
- [Transformation process](#transformation-process)
- [Embedded and array](#embedded-and-array)
- [Field path and dependencies](#field-path-and-dependencies)
- [Choices](#choices)
- [Buttons](#buttons)
- [Elements](#elements)
    - [StringElement](#stringelement)
        - [Email](#email)
        - [Url](#url)
    - [IntegerElement](#integerelement)
    - [FloatElement](#floatelement)
    - [BooleanElement](#booleanelement)
    - [DateTimeElement](#datetimeelement)
    - [PhoneElement](#phoneelement)
    - [CsrfElement](#csrfelement)
    - [AnyElement](#anyelement)
- [Create a custom element](#create-a-custom-element)
    - [Using custom form](#using-custom-form)
    - [Using LeafElement](#using-leafelement)
    - [Usage](#usage)
    - [Custom child builder](#custom-child-builder)
- [Error Handling](#error-handling)
    - [Simple usage](#simple-usage)
    - [Printer](#printer)

## Installation using composer

```
composer require b2pweb/bdf-form
```

## Basic usage

To create a form, simply extends the class [`CustomForm`](src/Custom/CustomForm.php) and implements method `CustomForm::configure()` :

```php
<?php

namespace App\Form;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;

class LoginForm extends CustomForm
{
    protected function configure(FormBuilderInterface $builder) : void
    {
        // Register inputs using builder
        // required() specify than the input value cannot be empty
        // setter() specify that the value will be exported when calling $form->value()
        $builder->string('username')->required()->setter();
        $builder->string('password')->required()->setter();

        // A button can also be declared (useful for handle multiple actions in one form)
        $builder->submit('login');
    }
}
```

To display the form, call the `ElementInterface::view()` method on the form object, and use the view object :

```php
<?php
// Instantiate the form (a container can be use for handle dependency injection)
$form = new LoginForm();
$view = $form->view(); // Get the form view

?>

<form method="post" action="login.php">
    <!-- Use array access for get form elements -->
    <!-- The onError() method will return the parameter only if the element is on error. This method also supports a callback as parameter -->
    <div class="input-group<?php echo $view['username']->onError(' has-error'); ?>">
        <label for="login-username">Username</label>
        <!-- You can configure attributes using magic method call : here it will add class="form-control" id="login-username" -->
        <!-- The view element can be transformed to string. The input html element, the value and the name will be renderer -->
        <?php echo $view['username']->class('form-control')->id('login-username'); ?>
        <!-- Render the error message -->
        <div class="form-control-feedback"><?php echo $view['username']->error(); ?></div>
    </div>
    <div class="input-group<?php echo $view['password']->onError(' has-error'); ?>">
        <label for="login-password">Password</label>
        <!-- If there is a conflict with a method name for add an attribute, you can use the method set() -->
        <?php echo $view['password']->class('form-control')->id('login-password')->set('type', 'password'); ?>
        <div class="form-control-feedback"><?php echo $view['password']->error(); ?></div>
    </div>
    
    <!-- Render the button -->
    <?php echo $view['login']->class('btn btn-primary')->inner('Login'); ?>
</form>
```

Now, you can submit data to the form, and perform validation :

```php
<?php

// Instantiate the form (a container can be use for handle dependency injection)
$form = new LoginForm();

// Submit and check if the form is valid
if (!$form->submit($_POST)->valid()) {
    // The form has an error : use `ElementInterface::error()` to get the error and render it
    echo 'Error : ', $form->error();
    return;
}

// The form is valid : get the value
$credentials = $form->value();

// $credentials is an array with elements values 
performLogin($credentials['username'], $credentials['password']);
```

## Handle entities

The form system can be use to import, create or fill an entity using accessors :
- For `FormInterface::import()` the entity, use `ChildInterface::getter()` on the corresponding field. This method will use [`Getter`](src/PropertyAccess/Getter.php) as extractor.
- For fill an entity, using `FormInterface::attach()` followed by `FormInterface::value()`, use `ChildInterface::setter()`. This method will use [`Setter`](src/PropertyAccess/Setter.php) as hydrator.
- For create a new instance of the entity, using `FormInterface::value()`, without `attach()`, use `FormBuilderInterface::generates()`. This method will use [`ValueGenerator`](src/Aggregate/Value/ValueGenerator.php).

Declaration :

```php
<?php

// Declare the entity
// The properties should be public, or has public accessors to be handled by the form
class Person
{
    /** @var string */
    public $firstName;
    /** @var string */
    public $lastName;
    /** @var DateTimeInterface|null */
    public $birthDate;
    /** @var Country|null */
    public $country;
}

class PersonForm extends \Bdf\Form\Custom\CustomForm
{
    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder) : void
    {
        // Define that PersonForm::value() should return a Person instance 
        $builder->generates(Person::class);
        
        // Declare fields with getter and setter
        $builder->string('firstName')->required()->getter()->setter();
        $builder->string('lastName')->required()->getter()->setter();
        $builder->dateTime('birthDate')->immutable()->getter()->setter();
        
        // Custom transformer can be declared with a callback as first parameter on getter() and setter() methods
        $builder->string('country')
            ->getter(function (Country $value) { return $value->code; })
            ->setter(function (string $value) { return Country::findByCode($value); })
        ;
    }
}
```

Usage :

```php
<?php

class PersonController extends Controller
{
    private $repository;
    
    // Get a form view with entity values
    public function editForm($request)
    {
        // Get the entity
        $person = $this->repository->find($request->query->get('id'));
        
        // Create the form, import the entity data, and create the view object
        $form = new PersonForm();
        $view = $form->import($person)->view();
        
        // The form view can be used: fields values are set
        return $this->render('person/form', ['form' => $view]);
    }
    
    // Use the form to create the entity
    public function create($request)
    {
        // Get the form instance
        $form = new PersonForm();
        
        // Submit form data
        if (!$form->submit($request->post())->valid()) {
            throw new FormError($form->error());
        }
        
        // $form->value() will return the filled entity
        $this->repository->insert($form->value());
    }
    
    // Update an existent entity: simply attach the entity to fill
    public function update($request)
    {
        // Get the entity
        $person = $this->repository->find($request->query->get('id'));
        
        // Get the form instance and attach the entity to update
        $form = new PersonForm();
        $form->attach($person);

        // Submit form data
        if (!$form->submit($request->post())->valid()) {
            throw new FormError($form->error());
        }
        
        // $form->value() will return the filled entity
        $this->repository->insert($form->value());
    }
    
    // Works like update, but apply only provided fields (HTTP PATCH method)
    // The entity must be import()'ed instead of attach()'ed
    public function patch($request)
    {
        // Get the entity
        $person = $this->repository->find($request->query->get('id'));
        
        // Get the form instance and import the entity to patch
        $form = new PersonForm();
        $form->import($person);

        // Submit form data
        if (!$form->patch($request->post())->valid()) {
            throw new FormError($form->error());
        }
        
        // $form->value() will return the filled entity
        $this->repository->insert($form->value());
    }
}
```

## Transformation process

Here a description, step by step, of the transformation process, from HTTP value to model value.
This process is reversible to generate HTTP value from model.

> Note: This example is for leaf element contained into a form. 
> For an embedded for or array, simply replace the leaf element process by the form process.

### 1 - Submit to buttons (scope: RootForm)

The first step perform by the `RootForm` is to check the submit buttons. 
If the HTTP data contains the button name, and the value match with the configured one, the button is marked as clicked.

> Note: in reverse process, the clicked button value will be added to HTTP value

See :
- `ButtonInterface::submit()` 
- `ButtonInterface::clicked()`
- `RootFormInterface::submitButton()`

### 2 - Call transformers of the form (scope: Form)

Transformers of the container form are called. There are used to normalize input HTTP data to usable array value.

> Note: the transformers are called in reverse order (i.e. last registered is first executed) when transform from HTTP to PHP
> and called in order for PHP to HTTP transformation

```php
// Declare a transformer
class JsonTransformer implements \Bdf\Form\Transformer\TransformerInterface
{
    public function transformToHttp($value,\Bdf\Form\ElementInterface $input)
    {
        return json_encode($value);
    }

    public function transformFromHttp($value,\Bdf\Form\ElementInterface $input)
    {
        return json_decode($value, true);
    }
}

class MyForm extends CustomForm
{
    protected function configure(FormBuilderInterface $builder): void
    {
        // Transform JSON input to associative array
        $builder->transformer(new JsonTransformer());
    }
}
```

See:
- `TransformerInterface`
- `FormBuilderInterface::transformer()`

### 3 - Extract the HTTP field value (scope: Child)

At this step, the normalized HTTP value is passed to the child, and the current field value is extracted.
If the value is not available, null is returned.

There is two extraction strategy :
- Array offset: This is the default strategy. Extract the field value using a simple array access like `$httpValue[$name]`. By default the HTTP field name is same as the child name.
- Array prefix: This strategy can only be used for aggregate elements like for or array. Filter the HTTP value, and keep only fields which starts with the given prefix.

See:
- `HttpFieldsInterface::extract()`
- `ChildBuilder::httpFields()`
- `ChildBuilder::prefix()`
- `ChildInterface::submit()`
 
### 4 - Apply filters (scope: Child)

Once the field value is extracted, filters are applied. There are used for normalize and remove illegal values. 
It's a destructive operation by definition (cannot be reversed), unlike transformers. There can be used for perform `trim` or `array_filter`.

> Note: Unlike transformers, filters are only applied during transformation from HTTP to PHP.
> Do not use if it's a "view" operation, like decoding a string.

```php
// Filter for keep only alpha numeric characters
class AlphaNumFilter implements \Bdf\Form\Filter\FilterInterface
{
    public function filter($value,\Bdf\Form\Child\ChildInterface $input,$default)
    {
        if (!is_string($value)) {
            return null;
        }

        return preg_replace('/[^a-z0-9]/i', '', $value);
    }
}

class MyForm extends CustomForm
{
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->string('foo')->filter(new AlphaNumFilter())->setter();
    }
}

$form = new MyForm();
$form->submit(['foo' => '$$$abc123___']);
$form->value()['foo'] === 'abc123'; // The string is filtered
```

See:
- `FilterInterface`
- `ChildBuilderInterface::filter()`

### 5 - Set default value (scope: Child)

Set the default value is the filtered field value is considered as empty and a default value is provided.
A value is empty is it's an empty string `''` or array `[]`, or it's  `null`. `0`, `0.0` or `false` are not considered as empty.
If not default value is given, the filtered value will be used.

> Note: To set a default value, you should call `ChildBuilderInterface::default()` with PHP value

See:
- `HttpValue::orDefault()`
- `ChildBuilderInterface::default()`

### 6 - Call element transformers (scope: Element)

Works like Form transformers (step 2), but on the element value.
If a transformer throws an exception, the submit process will be stopped, the raw HTTP value will be kept,
and the element will be marked as invalid with the exception message as error.

The transformer exception behavior can be changed on the `ElementBuilder`. 
If the exception is ignored by calling `ignoreTransformerException()` on the builder, the validation process will be performed on the raw HTTP value.

```php
// Declare a transformer
class Base64Transformer implements \Bdf\Form\Transformer\TransformerInterface
{
    public function transformToHttp($value,\Bdf\Form\ElementInterface $input)
    {
        return base64_encode($value);
    }

    public function transformFromHttp($value,\Bdf\Form\ElementInterface $input)
    {
        $value = base64_decode($value);
        
        // Throw exception on invalid value
        if ($value === false) {
            throw new InvalidArgumentException('Invalid base64 data');
        }

        return $value;
    }
}

class MyForm extends CustomForm
{
    protected function configure(FormBuilderInterface $builder): void
    {
        // "foo" is a base64 string input
        $builder
            ->string('foo')
            ->transformer(new Base64Transformer())
            ->transformerErrorMessage('Expecting base 64 data') // Define custom transformer error code and message
            ->transformerErrorCode('INVALID_BASE64_ERROR')
        ;
    }
}
```

See:
- `ElementBuilderInterface::transformer()`
- `ValidatorBuilderTrait::ignoreTransformerException()`
- `ValidatorBuilderTrait::transformerErrorMessage()`
- `ValidatorBuilderTrait::transformerErrorCode()`

### 7 - Cast to PHP value (scope: Element)

The value is converted from HTTP value to usable PHP value, like a cast to int on `IntegerElement`.

> Note: this step is only performed on `LeafElement` implementations

See:
- `LeafElement::toPhp()`
- `LeafElement::fromPhp()`

### 8 - Validation (scope: Element)

Validate the PHP value of the element, using constraints.

See:
- `ElementInterface::error()`
- `ElementInterface::valid()`
- `ElementBuilderInterface::satisfy()`

### 9 - Generate the form value (scope: Form)

Create the entity to fill by form values.

See:
- `ValueGeneratorInterface`
- `FormInterface::value()`
- `FormBuilderInterface::generator()`
- `FormBuilderInterface::generates()`

### 10 - Apply model transformer (scope: Child)

Call the model transformer, for transform input data to model data.

```php
class MyForm extends CustomForm
{
    protected function configure(FormBuilderInterface $builder): void
    {
        // The date should be saved as timestamp on the entity
        $builder
            ->dateTime('date')
            ->saveAsTimestamp()
            ->setter()
        ;

        // Save data as mongodb Binary        
        $builder
            ->string('data')
            ->modelTransformer(function ($value, $input, $toModel) {
                return $toModel ? new Binary($value, Binary::TYPE_GENERIC) : $value->getData();
            })
            ->setter()
        ;
    }
}
```

See:
- `ChildBuilderInterface::modelTransformer()`

### 11 - Call accessor (scope: Child)

The accessor is used to fill the entity (in case of HTTP to PHP), or form import from entity (in case of PHP to form).

See:
- `ChildBuilder::setter()`
- `ChildBuilder::getter()`
- `ChildBuilder::hydrator()`
- `ChildBuilder::extractor()`

### 12 - Validate the form value (scope: Form)

Once the value is hydrated, it will be validated by the form constraints.

```php
class MyForm extends CustomForm
{
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->generates(MyEntity::class);

        $builder->string('foo')->setter();
        $builder->string('bar')->setter();
        
        $builder->satisfy(function (MyEntity $entity) {
            // Validate the hydrated entity
            if (!$entity->isValid()) {
                return 'Invalid entity';
            }
        });
    }
}
```

See:
- `FormInterface::valid()`
- `FormInterface::error()`
- `FormBuilderInterface::satisfy()`

## Embedded and array

Complex form structure can be created using embedded form and generic array element.
Embedded form is useful for reuse a form into another.

```php
<?php 

class UserForm extends \Bdf\Form\Custom\CustomForm
{
    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder): void
    {
        // Define a sub-form "credentials", which generates a Credentials object
        $builder->embedded('credentials', function (\Bdf\Form\Child\ChildBuilderInterface $builder) {
            // $builder is type of ChildBuilderInterface, but forward call to FormBuilderInterface
            // So it can be used like a simple form builder
            
            $builder->generates(Credentials::class);
            $builder->string('username')->required()->length(['min' => 3])->getter()->setter();
            $builder->string('password')->required()->length(['min' => 6])->getter()->setter();
        });
        
        // Define an array of Address instances
        $builder->array('addresses')->form(function (FormBuilderInterface $builder) {
            $builder->generates(Address::class);
            $builder->string('address')->required()->getter()->setter();
            $builder->string('city')->required()->getter()->setter();
            $builder->string('zipcode')->required()->getter()->setter();
            $builder->string('country')->required()->getter()->setter();
        });

        // embedded and leaf fields can be mixed on the same form 
        $builder->string('email')->required()->getter()->setter();
    }
}
```

This form will handle data like :

```
[
    'credentials' => [
        'username' => 'jdoe',
        'password' => 'p@ssw04d'
    ],
    'addresses' => [
        ['address' => '147 Avenue du Parc', 'city' => 'Villes-sur-Auzon', 'zipcode' => '84148', 'country' => 'FR'],
        ['address' => '20 Rue de la paix', 'city' => 'Gordes', 'zipcode' => '84220', 'country' => 'FR'],
    ],
    'email' => 'jdoe@example.com',
]
```

Or in HTTP format :

```
credentials[username]=jdoe
&credentials[password]=p@ssw04d
&addresses[0][address]=147 Avenue du Parc
&addresses[0][city]=Villes-sur-Auzon
&addresses[0][zipcode]=84148
&addresses[0][country]=FR
&addresses[1][address]=20 Rue de la paix 
&addresses[1][city]=Gordes
&addresses[1][zipcode]=84220 
&addresses[1][country]=FR
&email=jdoe@example.com
```

To improve readability and reusability, each embedded form can be declared in its own class :


```php
<?php 

class CredentialsForm extends \Bdf\Form\Custom\CustomForm
{
    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder) : void
    {
        $builder->generates(Credentials::class);
        $builder->string('username')->required()->length(['min' => 3])->getter()->setter();
        $builder->string('password')->required()->length(['min' => 6])->getter()->setter();
    }
}

class AddressForm extends \Bdf\Form\Custom\CustomForm
{
    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder) : void
    {
        $builder->generates(Address::class);
        $builder->string('address')->required()->getter()->setter();
        $builder->string('city')->required()->getter()->setter();
        $builder->string('zipcode')->required()->getter()->setter();
        $builder->string('country')->required()->getter()->setter();
    }
}

class UserForm extends \Bdf\Form\Custom\CustomForm
{
    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder) : void
    {
        // Simply define element with the embedded form class name
        $builder->add('credentials', CredentialsForm::class);
        $builder->array('addresses', AddressForm::class);
        $builder->string('email')->required()->getter()->setter();
    }
}
```

You can also "flatten" the HTTP fields by using `ChildBuilderInterface::prefix()`. 
The embedded form will use a prefix instead of a sub-array.

```php
<?php

class UserForm extends \Bdf\Form\Custom\CustomForm
{
    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder) : void
    {
        // Simply define element with the embedded form class name
        $builder->add('credentials', CredentialsForm::class)->prefix();
        $builder->array('addresses', AddressForm::class);
        $builder->string('email')->required()->getter()->setter();
    }
}
```

Using prefix, the new data format is : 

```
[
    'credentials_username' => 'jdoe',
    'credentials_password' => 'p@ssw04d'
    'addresses' => [
        ['address' => '147 Avenue du Parc', 'city' => 'Villes-sur-Auzon', 'zipcode' => '84148', 'country' => 'FR'],
        ['address' => '20 Rue de la paix', 'city' => 'Gordes', 'zipcode' => '84220', 'country' => 'FR'],
    ],
    'email' => 'jdoe@example.com',
]
```

Or in HTTP format :

```
credentials_username=jdoe
&credentials_password=p@ssw04d
&addresses[0][address]=147 Avenue du Parc
&addresses[0][city]=Villes-sur-Auzon
&addresses[0][zipcode]=84148
&addresses[0][country]=FR
&addresses[1][address]=20 Rue de la paix 
&addresses[1][city]=Gordes
&addresses[1][zipcode]=84220 
&addresses[1][country]=FR
&email=jdoe@example.com
```

## Field path and dependencies

In some cases, a field value should be validated or transformed using another field value.
It's for this goal that field dependencies are added : when a field depends on other, you can declare it using `ChildBuilderInterface::depends()`.
Field path are used to access to the specific field.

> Note: dependencies add complexity to the form, it's advisable to use a constraint on the parent form if possible.

To create a field path (and access to the desired field), you should use `FieldPath::parse()`, or [`FieldFinderTrait`](src/Util/FieldFinderTrait.php).

The format works like unix file system path, with `/` as field separator, `.` for the current field, and `..` for the parent.
Use `/` at the start will define path as absolute.
Unlike unix path, by default, the path starts from the parent of the field (i.e. equivalent to `../`).

Format: 
```
[.|..|/] [fieldName] [/fieldName]...
```

With :
- `.` to start the path from the current element (and not from it's parent). The current element must be an aggregate element like a form to works
- `..` to start the path from the parent of the current element. This is the default behavior, so it's not necessary to start with "../" the path
- `/` is the fields separator. When used at the beginning of the path it means that the path is absolute (i.e. start from the root element)
- `fieldName` is a field name. The name is the declared one, not the HTTP field name

Usage:

```php
<?php 
// Using "low level" FieldPath helper
class CredentialsForm extends \Bdf\Form\Custom\CustomForm
{
    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder) : void
    {
        $builder->string('username');
        $builder->string('password');
        $builder->string('confirm')
            ->depends('password') // Password must be submitted before confirm
            ->satisfy(function ($value, \Bdf\Form\ElementInterface $input) {
                // Get sibling field value using FieldPath
                // Note: with FieldPath, the path is relative to the parent of the current field
                if ($value !== \Bdf\Form\Util\FieldPath::parse('password')->value($input)) {
                    return 'Confirm must be same as password';
                }
            })
        ;
    }
}

// Using FieldFinderTrait on custom form
class CredentialsForm extends \Bdf\Form\Custom\CustomForm
{
    use \Bdf\Form\Util\FieldFinderTrait;

    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder) : void
    {
        $builder->string('username');
        $builder->string('password');
        $builder->string('confirm')
            ->depends('password') // Password must be submitted before confirm
            ->satisfy(function ($value, \Bdf\Form\ElementInterface $input) {
                // Get sibling field value using findFieldValue
                // Note: with FieldFinderTrait, the path is relative to the custom form
                if ($value !== $this->findFieldValue('password')) {
                    return 'Confirm must be same as password';
                }
            })
        ;
    }
}
```

The `FieldPath` can also be used outside the form, and with embedded forms :

```php
use Bdf\Form\Util\FieldPath;

$form = new UserForm();

// Find the username field, starting from the root
// Start the expression with "." to not start the path from the parent of UserForm (which do not exists)
$username = FieldPath::parse('./embedded/username')->resolve($form);

// Also works from a "leaf" field
$password = FieldPath::parse('password')->resolve($username);
// Same as above
$password = FieldPath::parse('../password')->resolve($username);

// Absolute path : get "email" field of the root form
$email = FieldPath::parse('/email')->resolve($username);
```

## Choices

Choice system is use to allow only a set of values, like with HTML `<selecte>` element.
To define a choice, simply call `choice()` on the element builder, if supported. 
A label can be defined using the key of associative array for the list of available values.

```php
$builder->string('country')
    ->choices([
        'France' => 'FR',
        'United-Kingdom' => 'UK',
        'Spain' => 'ES',
    ])
;
```

Once defined, the view system will automatically transform simple input elements to `<select>`.
To render manually the choice, you can also call `FieldViewInterface::choices()` to get choices array :

```php
<select name="<?php echo $view->name(); ?>">
 <?php foreach ($view->choices() as $choice): ?>
     <option value="<?php echo $choice->value(); ?>"<?php echo $choice->selected() ? ' selected' : ''; ?>><?php echo $choice->label(); ?></option>
 <?php endforeach; ?>
</select>
```

## Buttons

Submit button can be defined to handle multiple action on the same form.

The form :

```php
<?php

class MyForm extends \Bdf\Form\Custom\CustomForm
{
    const SAVE_BTN = 'save';
    const DELETE_BTN = 'delete';

    protected function configure(\Bdf\Form\Aggregate\FormBuilderInterface $builder) : void
    {
        $builder->string('foo');
        
        // Define buttons
        $builder->submit(self::SAVE_BTN);
        $builder->submit(self::DELETE_BTN);
    }
}
```

The view :

```php
<?php 
$view = (new MyForm())->view();
?>

<form action="action.php" method="post">
    <?php echo $view['foo']; ?>
    
    <!-- Render the buttons. Use inner() to define the button text -->
    <?php echo $view[MyForm::SAVE_BTN]->inner('Save'); ?>
    <?php echo $view[MyForm::DELETE_BTN]->inner('Delete'); ?>
</form>
```

The controller :

```php
<?php

$form = new MyForm();

// Submit the form 
$form->submit($_POST);

// Get the submitted button name
// The submit button is handled by the root element
switch ($btn = $form->root()->submitButton() ? $btn->name() : null) {
    case MyForm::SAVE_BTN:
        doSave($form->value());
        break;

    case MyForm::DELETE_BTN:
        doDelete($form->value());
        break;
        
    default:
        throw new Exception();
}
```

## Elements

### StringElement

```php
$builder->string('username')
    ->length(['min' => 3, 'max' => 32]) // Define length options
    ->regex('/[a-z0-9_-]+/i') // Define a validation regex
;
```

#### Email

String element with Email constraint

```php
$builder->email('username')->mode(Email::VALIDATION_MODE_HTML5);
```

#### Url

String element with Url constraint

```php
$builder->url('server')->protocols('ftp', 'sftp');
```

### IntegerElement

```php
$builder->integer('number')
    ->posivite() // Same as ->min(0)
    ->min(5) // The number must be >= 5
    ->max(9999) // The element must be <= 9999
    ->grouping(true) // The HTTP value will group values by thousands (i.e. 145 000 instead of 145000)
    ->raw(false) // If set to true, the input will be simply caster to int, and not transformed following the locale
;
```


### FloatElement

```php
$builder->float('number')
    ->posivite() // Same as ->min(0)
    ->min(1.1) // The number must be >= 1.1
    ->max(99.99) // The element must be <= 99.99
    ->grouping(true) // The HTTP value will group values by thousands (i.e. 145 000 instead of 145000)
    ->scale(2) // Only consider 2 digit on the decimal part
    ->raw(false) // If set to true, the input will be simply caster to int, and not transformed following the locale
;
```

### BooleanElement

Handle boolean value, like a checkbox.
The value will be considered as true if it's present, and is equals to the defined one (by default `1`).

> Note: in HTTP a falsy value is an absent value, so a default value cannot be defined on a boolean element.
> To define a "view default" value, use `ElementBuilderInterface::value()` instead of `ChildElementBuilder::default()`

The default renderer will render a `<input type="checkbox" />` with the defined http value and checked state.

```php
$builder->boolean('enabled')
    ->httpValue('enabled') // Define the requireds HTTP field value
;
```

### DateTimeElement

```php
$builder->dateTime('eventDate')
    ->className(Carbon::class) // Define a custom date class
    ->immutable() // Same as ->className(DateTimeImmutable::class)
    ->format('d/m/Y H:i') // Define the parsed date format
    ->timezone('Europe/Paris') // Define the parse timezone. The element PHP value will be on this timezone
    ->before(new DateTime('+1 year')) // eventDate must be before next year
    ->beforeField('eventDateEnd') // Compare the field value to a sibling field (eventDateEnd)
    ->after(new DateTime()) // eventDate must be after now
    ->afterField('accessDate') // Compare the field value to a sibling field (accessDate)
```

### PhoneElement

Handle phone number. The package `giggsey/libphonenumber-for-php` is required to use this element.
This element will not return a `string` but a `PhoneNumber` instance. 

```php
$builder->phone('contact')
    ->regionResolver(function () {
        return $this->user()->country(); // Resolve the phone region using a custom resolver
    })
    ->region('FR') // Force the region value for parse the phone number
    ->regionInput('address/country') // Use a sibling input for parse the number (do not forget to call `depends()`)
    ->allowInvalidNumber(true) // Do not check the phone number
    ->validateNumber('My error message') // Enable validation, and define the validator options (here the error message)
    ->setter()->saveAsString() // Save the phone number as string on the entity
;
```

### CsrfElement

This element allows to mitigate CSRF. The package `symfony/security-csrf` is required for this element.
Some element methods are disallowed, like `import()`, any constraints, transformer or default.
The view will be rendered as `<input type="hidden" />`.

```php
$builder->csrf() // No need to set a name. by default "_token"
    ->tokenId('my_token') // Define the token id. By default is use CsrfElement::class, but define a custom tokenId permit to not share CSRF token between forms
    ->message('The token is invalid') // Define a custom error message
    ->invalidate(true) // The token is always invalidated after check, and should be regenerated
;
```

### AnyElement

An element for handle any value types. This is useful for create an inline custom element.
But it's strongly discouraged : prefer use one of the native element, or create a custom one.

```php
$builder->add('foo', AnyElement::class) // No helper method are present
    ->satisfy(function ($value) { ... }) // Configure the element
    ->transform(function ($value) { ... })
;
```

## Create a custom element

You can declare custom elements to handle complex types, and reuse into any forms.
The examples bellow are for declare an element to handle [`UriInterface`](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface) objects, created using PSR-17 [`UriFactoryInterface`](https://www.php-fig.org/psr/psr-17/#26-urifactoryinterface)

### Using custom form

You can use a `CustomForm` to declare an element. 
The advantage of this method is that it don't need to implements low level interfaces, and require only to extends the `CustomForm` class.
But it has the consequence of using more resources, has a lower flexibility, and cannot define a custom builder.

```php
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;

// Declare the element class
class UriElement extends CustomForm
{
    const INNER_ELEMENT = 'uri';

    /**
    * @var UriFactoryInterface 
    */
    private $uriFactory;
    
    // Set the UriFactoryInterface at the constructor
    public function __construct(?FormBuilderInterface $builder = null, ?UriFactoryInterface $uriFactory = null) 
    {
        parent::__construct($builder);
        
        $this->uriFactory = $uriFactory ?? new DefaultUriFactory(); // Default instance for the factory
    }

    protected function configure(FormBuilderInterface $builder) : void
    {
        // Define inner element to store value
        // The URI is basically a string
        $builder->string(self::INNER_ELEMENT);

        // The UriElement is a form, and supports only array values
        // Define a transformer to remap value to the inner element
        $builder->transformer(new class implements TransformerInterface {
            public function transformToHttp($value, ElementInterface $input)
            {
                // $value is an array of children value
                // Return only the inner element value
                return $value[UriElement::INNER_ELEMENT];
            }
            
            public function transformFromHttp($value, ElementInterface $input)
            {
                // $value is the URI string
                // Map it as array to the inner element
                return [UriElement::INNER_ELEMENT => $value];
            }           
        });

        // Define the value generator : parse the inner element value to UriInterface using the factory        
        $builder->generates(function () {
            return $this->uriFactory->createUri($this[self::INNER_ELEMENT]->element()->value());
        });
    }
}
```

### Using LeafElement

If declaring an element using the `CustomForm` is not enough, or if you want to optimise this element, you can use the low level class [`LeafElement`](src/Leaf/LeafElement.php) to declare a custom element.

```php
use Bdf\Form\Choice\ChoiceInterface;
use Bdf\Form\Leaf\LeafElement;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;

// Declare the element using LeafElement class
class UriElement extends LeafElement
{
    /**
     * @var UriFactory 
     */
    private $uriFactory;

    // Overrides constructor to add the factory
    public function __construct(?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null, ?ChoiceInterface $choices = null, ?UriFactory $uriFactory = null) 
    {
        parent::__construct($validator, $transformer, $choices);
        
        $this->uriFactory = $uriFactory ?? new DefaultUriFactory(); // Use default instance
    }

    // Parse the HTTP string value using the factory
    protected function toPhp($httpValue): ?UriInterface
    {
        return $httpValue ? $this->uriFactory->createUri($httpValue) : null;
    }
    
    // Stringify the UriInterface instance
    protected function toHttp($phpValue): ?string
    {
        return $phpValue === null ? null : (string) $phpValue;
    }
}

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\ElementInterface;

// Declare the builder
class UriElementBuilder extends AbstractElementBuilder
{
    use ChoiceBuilderTrait; // Enable choices building
    
    /**
     * @var UriFactory
     */
    private $uriFactory;
    
    public function __construct(?RegistryInterface $registry = null, ?UriFactory $uriFactory = null) 
    {
        parent::__construct($registry);

        $this->uriFactory = $uriFactory ?? new DefaultUriFactory();
    }
    
    // You can define custom builder methods for constrains or 
    public function host(string $hostName): self
    {
        return $this->satisfy(function (?UriInterface $uri) use($hostName) {
            if ($uri->getHost() !== $hostName) {
                return 'Invalid host name';
            }
        });
    }

    // Create the element
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer) : ElementInterface
    {
        return new UriElement($validator, $transformer, $this->getChoices(), $this->uriFactory);
    }
}

// Register the element builder on the registry
// Use container to inject dependencies (here the UriFactoryInterface)
$registry->register(UriElement::class, function(Registry $registry) use($container) {
    return new UriElementBuilder($registry, $container->get(UriFactoryInterface::class));
});
```

### Usage

To use the custom element, simply call `FormBuilderInterface::add()` with the element class name as second parameter :

```php
$builder->add('uri', UriElement::class);
```

### Custom child builder

In some case, defining a custom child builder can be relevant, like for register model transformers.
To declare the child, simply extends `ChildBuilder` class, and register to the `Registry` :

```php
class MyCustomChildBuilder extends ChildBuilder
{
    public function __construct(string $name, ElementBuilderInterface $elementBuilder, RegistryInterface $registry = null)
    {
        parent::__construct($name, $elementBuilder, $registry);

        // Add a filter provider
        $this->addFilterProvider([$this, 'provideFilter']);
    }
    
    // Model transformer helper method
    public function saveAsCustom()
    {
        return $this->modelTransformer(new MyCustomTransformer());
    }
    
    // Provide default filter
    protected function provideFilter()
    {
        return [new MyFilter()];
    }
}

// Now you can register the child builder with the element builder
$registry->register(CustomElement::class, CustomElementBuilder::class, MyCustomChildBuilder::class);
```

## Error Handling

When an error occurs on the form, a [`FormError`](src/Error/FormError.php) object is created with all errors.

### Simple usage 

If an error is on a child, use `FormError::children()` to get the error.
If there is no error on children, but on the parent element, use `FormError::global()` instead.
To get a simple string representation of errors, cast the errors to string.
To get an array representation in form [fieldName => error], use `FormError::toArray()`.

```php
if (!$form->error()->empty()) {
    // Simple render errors as string
    return new Response('<div class="alert alert-danger">'.$form->error().'</div>');
    
    // For simple API error system, use toArray()
    return new JsonReponse(['error' => $form->error()->toArray()]);
    
    // Or use accessors
    foreach ($form->error()->children() as $name => $error) {
        echo $error->field().' : '.$error->global().' ('.$error->code().')'.PHP_EOL;
    }
}
```

### Printer

To format errors with reusable way, a [`FormErrorPrinterInterface`](src/Error/FormErrorPrinterInterface.php) can be implemented.

```php
/**
 * Print errors in format [httpField => ['code' => errorCode, 'message' => errorMessage]]
 */
class ApiPrinter implements \Bdf\Form\Error\FormErrorPrinterInterface
{
    private $errors = [];
    private $current;

    // Define the error message for the current element
    public function global(string $error) : void
    {
        $this->errors[$this->current]['message'] = $error;
    }
    
    // Define the error code for the current element
    public function code(string $code) : void
    {
        $this->errors[$this->current]['code'] = $code;
    }
    
    // Define the error element http field
    public function field(\Bdf\Form\Child\Http\HttpFieldPath $field) : void
    {
        $this->current = $field->get();
    }
    
    // Iterate on element's children
    public function child(string $name,\Bdf\Form\Error\FormError $error) : void
    {
        $error->print($this);
    }
    
    // Get all errors
    public function print()
    {
        return $this->errors;
    }
}

// Usage
return new JsonReponse(['errors' => $form->error()->print(new ApiPrinter())]);
```
