<?php

namespace wrd;

global $THEMEEXTENSIONS;
$THEMEEXTENSIONS = [];

class ThemeExtension
{
    const NOT_INSTALLED = "not_installed";
    const INSTALLED_INACTIVE = "installed_inactive";
    const ACTIVE = "active";

    public $slug;    // The plugin file path (relative to the plugins directory). E.g. plugin-directory/plugin-file.php
    public $version; // Version of the extension.

    public $title;          // Name of the extension.
    public $description;    // Describe what the extension does.
    public $author_name;    // Who owns the extension.
    public $author_uri;     // Website for the authors.
    public $settings_page;  // Back-end extension settings page URL (if there is one).

    function __construct($data)
    {
        $this->slug = WRD::array_fallback($data, "slug", false);

        if (!$this->slug) {
            throw new \InvalidArgumentException("The 'slug' field is required for the ThemeExtension class.");
        }

        $this->version = WRD::array_fallback($data, "version", "1.0.0");

        $this->title            = WRD::array_fallback($data, "title", "Direct Theme Extension");
        $this->description      = WRD::array_fallback($data, "description", "Lorem ipsum dulcet et delores et.");
        $this->author_name      = WRD::array_fallback($data, "author_name", "Author");
        $this->author_uri       = WRD::array_fallback($data, "author_uri", "");
        $this->settings_page    = WRD::array_fallback($data, "settings_page", "");

        global $THEMEEXTENSIONS;
        $THEMEEXTENSIONS[$this->slug] = &$this;
    }

    static function get_all()
    {
        global $THEMEEXTENSIONS;
        return $THEMEEXTENSIONS;
    }

    function get_status()
    {
        $plugin_file = $this->slug;

        if (!file_exists(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_file)) {
            return static::NOT_INSTALLED;
        }

        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

        if (in_array($plugin_file, $active_plugins)) {
            return static::ACTIVE;
        }

        return static::INSTALLED_INACTIVE;
    }
}
