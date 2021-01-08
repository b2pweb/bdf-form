<?php

namespace Bdf\Form\Util;

use Bdf\Form\Aggregate\ChildAggregateInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;

/**
 * Represents a path for resolve a field
 * This path can be absolute (start from the root element) or relative (start from the current element)
 *
 * @see FieldPath::parse() To create the path from a string
 */
final class FieldPath
{
    const SELF_ELEMENT = '.';
    const PARENT_ELEMENT = '..';
    const SEPARATOR = '/';

    /**
     * Paths cache
     *
     * @var FieldPath[]
     */
    private static $cache = [];

    /**
     * @var string[]
     */
    private $path;

    /**
     * @var bool
     */
    private $absolute;


    /**
     * FieldPath constructor.
     *
     * @param string[] $path The path
     * @param bool $absolute true to start the resolution from the root element
     *
     * @see FieldPath::parse() Prefer use this method instead of the constructor
     */
    public function __construct(array $path, bool $absolute)
    {
        $this->path = $path;
        $this->absolute = $absolute;
    }

    /**
     * Resolve the element using the path from the current element
     *
     * @param ElementInterface|ChildInterface $currentElement The base element
     *
     * @return ElementInterface|null The resolved element, or null if cannot be found
     */
    public function resolve($currentElement): ?ElementInterface
    {
        if ($currentElement instanceof ChildInterface) {
            $currentElement = $currentElement->element();
        }

        if ($this->absolute) {
            $currentElement = $currentElement->root();
        }

        foreach ($this->path as $part) {
            if ($part === self::PARENT_ELEMENT) {
                if (($container = $currentElement->container()) === null) {
                    return null;
                }

                $currentElement = $container->parent();
                continue;
            }

            if (!$currentElement instanceof ChildAggregateInterface || !isset($currentElement[$part])) {
                return null;
            }

            $currentElement = $currentElement[$part]->element();
        }

        return $currentElement;
    }

    /**
     * Resolve sibling element value
     *
     * Usage:
     * <code>
     * $builder->string('password');
     * $builder->string('confirmation')->depends('password')->satisfy(function ($value, $input) {
     *     if ($value !== FieldPath::resolve('password')->value($input)) {
     *         return 'Invalid';
     *     }
     * })
     * </code>
     *
     * @param ElementInterface|ChildInterface $currentElement The base element
     *
     * @return mixed|null The element value, or null if not found
     */
    public function value($currentElement)
    {
        if (!$element = $this->resolve($currentElement)) {
            return null;
        }

        return $element->value();
    }

    /**
     * Parse a field path
     *
     * The format is :
     * [.|..|/] [fieldName] [/fieldName]...
     *
     * With :
     * - "." to start the path from the current element (and not from it's parent). The current element must be an aggregate element like a form to works
     * - ".." to start the path from the parent of the current element. This is the default behavior, so it's not necessary to start with "../" the path
     * - "/" is the fields separator. When used at the beginning of the path it means that the path is absolute (i.e. start from the root element)
     * - "fieldName" is a field name. The name is the declared one, not the HTTP field name
     *
     * Example:
     * FieldPath::parse("firstName") : Access to the sibling field named "firstName"
     * FieldPath::parse("../firstName") : Same as above
     * FieldPath::parse(".") : References the current element
     * FieldPath::parse("person/firstName") : Get the field "firstName" under the sibling embedded form "person"
     * FieldPath::parse("/foo/bar") : Get the field "bar" under "foo", starting from the root element
     *
     * @param string $path Path as string
     *
     * @return self
     */
    public static function parse(string $path): self
    {
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        if ($path[0] === self::SEPARATOR) {
            return self::$cache[$path] = new self(explode(self::SEPARATOR, substr($path, 1)), true);
        }

        $parts = explode(self::SEPARATOR, $path);

        switch ($parts[0]) {
            case self::SELF_ELEMENT:
                array_shift($parts);
                break;

            case self::PARENT_ELEMENT:
                break;

            default:
                array_unshift($parts, self::PARENT_ELEMENT);
        }

        return self::$cache[$path] = new self($parts, false);
    }
}
