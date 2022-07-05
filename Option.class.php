<?php

namespace wrd;

define('WRD_SETTINGS_PREFIX', 'wrd_setting_');

define('OPTIONS_DIR', __DIR__ . '/option-inputs/');
define('OPTIONS_URL', WRD::dir_to_url() . '/option-inputs/');

/**
 * Setups and manages a WordPress setting including the database value, saving and rendering. This base class provides a text input and can be overwritten for other input types.
 */
class Option
{
    function __construct($data)
    {
        /**
         * Creates an instance of WP_Setting.
         * 
         * Inheriting classes should not overwrite __construct. WP_Setting::setup() is provided for subclasses to initialise their data.
         * Any additional options sent to __construct are stored under $this->data for reference. This can be useful for an inheriting classes' settings.
         * 
         * @param array $data{
         * Required. Array of options to configure the setting.
         * 
         * 
         *      @type string $name                       Required. Acts as both the label and slug for the field.
         *      @type WP_Settings_Section $section       Required. Group of settings to be included in. Automatically adds to that WP_settings_Section variable.
         *      @type WP_Settings_Page $page             Required. Admin page to be shown on. Should be parent of $section.
         * 
         *      @type mixed $default                     Optional. The default value for the field.
         * }
         * 
         * @return WP_Setting
         */
        $this->data = $data;

        $this->name = $data['name'];
        $this->label = @$data['label'] ?: $data['name'];
        $this->description = @$data['description'] ?: "";
        $this->slug = Option::setting_slug($this->name, "WP_Setting");
        $this->section = $data['section']->slug;
        $this->page = array_key_exists('page', $data) ? $data['page']->slug : $data['section']->page;

        if (array_key_exists('default', $data)) {
            $this->default = $data['default'];
        } else {
            $this->default = null;
        }

        $data['section']->add_setting($this);

        add_action('admin_init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));

        $this->setup($data);
    }

    /**
     * Generates a slug for any WP_Setting classes from a title.
     * 
     * @param string $title Required. Title to convert to slug.
     * @param string $class Optional. Class type to create slug for (WP_Setting, WP_Settings_Page, WP_Settings_Page). Defaults to WP_Setting.
     */
    static function setting_slug($title, $class = "WP_Setting")
    {
        switch ($class) {
            case "WP_Settings_Page":
            case "OptionPage":
                return WRD_SETTINGS_PREFIX . 'page_' . sanitize_title($title);
                break;
            case "WP_Settings_Section":
            case "OptionSection":
                return WRD_SETTINGS_PREFIX . 'section_' . sanitize_title($title);
                break;
            case "WP_Setting":
            case "Option":
                return WRD_SETTINGS_PREFIX . sanitize_title($title);
                break;
        }
    }

    static function get_setting($setting, $default = false)
    {
        $option = get_option(Option::setting_slug($setting), $default);

        if (strtolower($option) === "true") {
            return true;
        } elseif (strtolower($option) === "false") {
            return false;
        }

        return $option;
    }

    /**
     * Configures object settings on initialisation.
     * 
     * Overwriteable: Inheriting classes can use this to initialise options.
     * 
     * @param array $data   Required. The constructor options array.
     * 
     * @return void
     */
    function setup($data)
    {
    }

    /**
     * For internal use only, use WP_Setting::render() instead. Displays the setting input HTML. It does not include the wrapping content.
     * 
     * @see WP_Setting::render()
     * 
     * Overwriteable: Inheriting classes should include a HTML input with the name of $this->slug.
     * 
     * @return void
     */
    function render_callback()
    {
        $name = $this->slug;
        $val = $this->get_value();

        echo "<input name='$name' value='$val'>";
    }

    /**
     * Runs on the admin_enqueue_scripts callback to enqueu setting scripts/styles.
     * 
     * Overwritable: Empty in the base class.
     * 
     * @param string $hook The current admin page.
     * 
     * @see admin_enqueue_scripts Hook
     * @link https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
     * 
     * @return void
     */
    function enqueue($hook)
    {
    }

    /**
     * Registers the setting with WordPress.
     * 
     * Overwriteable: Inheriting classes may store multiple values per WP_Setting object.
     * 
     * @see register_setting
     * @link https://developer.wordpress.org/reference/hooks/register_setting/
     * 
     * @return void
     */
    function register_setting()
    {
        register_setting($this->page, $this->slug);
    }

    /**
     * Returns the stored value of the setting. Uses the object default as fallback.
     * 
     * Overwriteable: Inheriting classes may parse their data differently or use defaults differently.
     * 
     * @return mixed value
     */
    function get_value()
    {
        return get_option($this->slug, $this->default);
    }

    /**
     * Initialises the setting field on the admin_init hook.
     * 
     * @see admin_init
     * @link https://developer.wordpress.org/reference/hooks/admin_init/
     * 
     * @return void
     */
    function init()
    {
        $this->add_field();
        $this->register_setting();
    }

    /**
     * Displays the setting including it's required wrapping content.
     * 
     * Should not be overwritten by inheriting classes.
     * 
     * @see WP_Setting::render_callback()
     * 
     * @return void;
     */
    function render()
    {
        $attrs = $this->get_wrapper_attrs();

        echo "<div $attrs>";
        echo $this->render_callback();
        echo "</div>";
    }

    /**
     * Registers setting with WordPress.
     * 
     * @see add_setting_field()
     * 
     * @return void
     */
    function add_field()
    {
        add_settings_field(
            $this->slug,
            $this->name,
            array($this, 'render_callback'),
            $this->page,
            $this->section,
            [
                "slug" => $this->slug,
                "value" => $this->get_value()
            ]
        );
    }

    /**
     * Creates HTML class string dependent on the state of the setting. E.g. adds wp-setting-disabled for conditional fields.
     * 
     * @param array $classes Optional. Array of default classes.
     * 
     * @return string
     */
    function get_wrapper_attrs()
    {
        $attrs = [
            "id" => $this->slug,
            "class" => "wp-setting-wrapper "
        ];

        // Conditional fields
        if (array_key_exists('condition', $this->data)) {
            $attrs["data-wp-setting-condition"] = "#" . $this->data['condition']->slug;

            if ($this->data['condition']->get_value() == false) {
                $attrs["class"] .= "wp-setting-disabled ";
            }
        }

        return $this->attrs_to_string($attrs);
    }

    /**
     * Converts array of attributes to HTML string.
     * 
     * @param array $attrs Required. Array of arrays attributes to convert.
     */
    function attrs_to_string($attrs)
    {
        $output = "";

        foreach ($attrs as $attribute => $value) {

            $output .= " $attribute='$value'";
        }

        return $output;
    }
}


/**
 * Gets setting database values for fields managed by WP_Settings.
 * 
 * String values for true and false are converted to booleans.
 * 
 * @param string $setting Required. Name of the setting, automatically converted to the slug.
 * @param mixed $default. Optional. Fallback value if the setting fallback is not found. Defaults to false.
 */
function get_theme_option($setting, $default = false)
{
    return Option::get_setting($setting, $default);
}


include_once OPTIONS_DIR . '/button/button.php';
include_once OPTIONS_DIR . '/checkbox/checkbox.php';
include_once OPTIONS_DIR . '/color/color.php';
include_once OPTIONS_DIR . '/color-shades/color-shades.php';
include_once OPTIONS_DIR . '/font-picker/font-picker.php';
include_once OPTIONS_DIR . '/font-weight/font-weight.php';
include_once OPTIONS_DIR . '/input/input.php';
include_once OPTIONS_DIR . '/media/media.php';
include_once OPTIONS_DIR . '/select/select.php';
include_once OPTIONS_DIR . '/textarea/textarea.php';
include_once OPTIONS_DIR . '/palette/palette.php';
