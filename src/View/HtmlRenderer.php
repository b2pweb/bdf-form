<?php

namespace Bdf\Form\View;

/**
 * Utility for render html elements
 */
final class HtmlRenderer
{
    /**
     * Render HTML attributes
     * The attributes consists of an associative array, with attribute name as key, and string or boolean attributes value as value
     * If the value is true, a simple flag is renderer (i.e. attribute without value)
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function attributes(array $attributes): string
    {
        $out = '';

        foreach ($attributes as $k => $v) {
            if ($v === false) {
                continue;
            }

            $out .= ' '.htmlentities($k);

            if ($v !== true) {
                $out .= '="'.htmlentities((string) $v).'"';
            }
        }

        return $out;
    }

    /**
     * Render an HTML element
     * - If $content is null the format is : "< {name} {attributes} />"
     * - Else the format is : "< {name} {attributes} > {content} </ {name} >"
     *
     * @param string $name The element tag name
     * @param array $attributes The attributes
     * @param string|null $content The element content. If null, a "void element" will be renderer
     *
     * @return string
     */
    public static function element(string $name, array $attributes, ?string $content = null): string
    {
        if ($content === null) {
            return '<'.$name.self::attributes($attributes).' />';
        }

        return '<'.$name.self::attributes($attributes).'>'.$content.'</'.$name.'>';
    }
}
