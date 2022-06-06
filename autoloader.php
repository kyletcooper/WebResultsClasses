<?php

namespace wrd;

if (!function_exists("wrd_autoload")) {

    function wrd_autoload(array $whitelist = [])
    {
        $classes = [
            "WRD",
            "ReportableError",

            "Email",
            "Rewrite",

            "ThemeBase",
            "ThemeExtension",

            "CustomTable",
            "CustomUser",
            "CustomPost",
            "CustomField",
            "CustomEditor",
            "CustomCreator",

            "UserMessage",
            "UserNotification",

            "Filter",
            "FilterArgument",

            "Metabox",
            "MetaboxTaxonomy",

            "Option",
            "OptionPage",
            "OptionSection",

            "Schema",
            "Navbar",
        ];

        foreach ($classes as $class) {
            $file = __DIR__ . "/$class.class.php";

            if (!file_exists($file)) {
                throw new \Exception("Class not found in WRD autoloader.");
            }

            if (class_exists("wrd\\$class")) {
                continue;
            }

            if (count($whitelist) > 0 && !in_array($class, $whitelist)) {
                continue;
            }

            require_once $file;
        }
    }
}
