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
    private $path = '';

    /**
     * @var string
     */
    private $prefix = '';

    /**
     * HttpFieldPath constructor.
     * Note: prefer use the static methods instead of the constructor
     *
     * @param string $path The path
     * @param string $prefix The prefix
     */
    public function __construct(string $path = '', string $prefix = '')
    {
        $this->path = $path;
        $this->prefix = $prefix;
    }

    /**
     * Add a new sub array key to the field path :
     * - If the current path is the root (i.e. empty path), will return a path consisting of the name
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

        $newPath->path = empty($this->path) ? $this->prefix.$name : $this->path.'['.$this->prefix.$name.']';
        $newPath->prefix = '';

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
        if (empty($this->path)) {
            return $this->prefix;
        }

        if (empty($this->prefix)) {
            return $this->path;
        }

        return $this->path.'['.$this->prefix.']';
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
        return new HttpFieldPath('', $prefix);
    }
}
