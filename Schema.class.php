<?php

namespace wrd;

class Schema
{
    /**
     * Displays a piece of schema microdata.
     * 
     * @param string $itemprop The itemprop attribute.
     * @param string $content The value of the property.
     * 
     * @see schema.org
     */
    static function the_microdata(string $itemprop, string $content)
    {
        if (filter_var($content, FILTER_VALIDATE_URL) === FALSE) {
            echo "<meta itemprop='$itemprop' content='$content'>";
        } else {
            echo "<link itemprop='$itemprop' href='$content'>";
        }
    }

    static function terms_to_comma_string($arr, $seperator = ", ")
    {
        $str = "";

        foreach ($arr as $term) {
            $str .= $term->name . $seperator;
        }

        $str = substr($str, 0, -1);

        return $str;
    }
}
