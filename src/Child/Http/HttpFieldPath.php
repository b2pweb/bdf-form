<?php

namespace Bdf\Form\Child\Http;

/**
 * Represents the HTTP field path to build
 * Handles prefix and array key fields
 *
 * Note: This class is immutable, any modifier will return a new instance
 *
 * <code>
 * $path = HttpFieldPath::named('root'); // Create the root field
 * echo $path->get(); // "root"
 * echo $path->add('bar')->get(); // Array element field : "root[bar]"
 * echo $path->prefix('p_')->add('bar')->get(); // Add a prefix for the sub element : "root[p_bar]"
 * </code>
 */
final class HttpFieldPath
{
    /**
     * @var HttpFieldPath|null
     */
    private static $empty;

    /**
     * @var string
     */
    private $root = '';

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string
     */
    private $prefix = '';

    /**
     * HttpFieldPath constructor.
     * Note: prefer use the static methods instead of the constructor
     *
     * @param string $root
     * @param string $path The path
     * @param string $prefix The prefix
     */
    public function __construct(string $root = '', string $path = '', string $prefix = '')
    {
        $this->root = $root;
        $this->path = $path;
        $this->prefix = $prefix;
    }

    /**
     * Add a new sub array key to the field path :
     * - If the current path is the root (i.e. empty path), will return a path consisting of the name by setting the "root" field value
     * - If the path is not the root (i.e. not empty path), the name will be added at end, enclosed by "[]"
     * - In any case, if there is a prefix, it will be added before the name
     *
     * @param string $name The element name to add
     *
     * @return static The new path instance
     */
    public function add(string $name): self
    {
        $newPath = clone $this;

        if ($this->root === '') {
            $newPath->root = $this->prefix.$name;
        } else {
            $newPath->path .= '['.$this->prefix.$name.']';
        }

        $newPath->prefix = '';

        return $newPath;
    }

    /**
     * Concatenate two field paths
     * The result consists of the current path followed by the $other path
     *
     * - If the other path has a root but not the current one, use the root and path of other, prefixed by the current prefix, and replace current prefix by the prefix of other
     * - If the other path has a root and also the current one, append the root and the path of the other to the current, wrap the other's root with [], and replace current prefix by the prefix of other
     * - The the other as no root, only append its prefix to the current one
     *
     * @param HttpFieldPath $other The next field path
     *
     * @return static The new path instance
     */
    public function concat(HttpFieldPath $other): self
    {
        $newPath = clone $this;

        if ($other->root !== '') {
            if ($this->root === '') {
                $newPath->root = $this->prefix.$other->root;
                $newPath->path = $other->path;
            } else {
                $newPath->path .= '['.$this->prefix.$other->root.']'.$other->path;
            }

            $newPath->prefix = $other->prefix;
        } else {
            $newPath->prefix .= $other->prefix;
        }

        return $newPath;
    }

    /**
     * Add a prefix for the next element
     * The prefix will be appended at the end of previous prefixes, so when chaining prefixed, the prefixes order will be kept
     *
     * @param string $name
     *
     * @return static The new path instance
     */
    public function prefix(string $name): self
    {
        $newPath = clone $this;

        $newPath->prefix .= $name;

        return $newPath;
    }

    /**
     * Get the string value of the path
     *
     * @return string
     */
    public function get(): string
    {
        $path = $this->root.$this->path;

        if ($path === '') {
            return $this->prefix;
        }

        if (empty($this->prefix)) {
            return $path;
        }

        return $path.'['.$this->prefix.']';
    }

    /**
     * Does the current path is a prefix path ?
     *
     * @return bool
     */
    public function isPrefix(): bool
    {
        return $this->prefix !== '';
    }

    /**
     * Does the current field is a root field ?
     *
     * @return bool
     */
    public function isRootField(): bool
    {
        return $this->root !== '' && $this->path === '';
    }

    /**
     * Call ->get()
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->get();
    }

    /**
     * Get the empty path (i.e. root path)
     *
     * @return HttpFieldPath
     */
    public static function empty(): HttpFieldPath
    {
        if (self::$empty) {
            return self::$empty;
        }

        return self::$empty = new HttpFieldPath();
    }

    /**
     * Get a simple path consisting of the name
     *
     * @param string $name
     *
     * @return HttpFieldPath
     */
    public static function named(string $name): HttpFieldPath
    {
        return new HttpFieldPath($name);
    }

    /**
     * Get a path with a prefix
     *
     * @param string $prefix
     *
     * @return HttpFieldPath
     */
    public static function prefixed(string $prefix): HttpFieldPath
    {
        return new HttpFieldPath('', '', $prefix);
    }
}
