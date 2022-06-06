<?php

namespace wrd;

/**
 * Sets up and manages a section of a WordPress admin page. Contains a set of WP_Setting objects.
 */
class OptionSection
{
    /**
     * Creates a OptionSection instance.
     * 
     * @param array $data {
     *      Required. Array of options for the section.
     * 
     *      @type string $title             Required. Title to label the section. Also becomes the slug.
     *      @type WP_Settings_Page $page    Required. The page to display the section on.
     *      @type string $description       Optional. Descriptive text to accompany the section.
     * }
     */
    function __construct($data)
    {
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->slug = Option::setting_slug($this->title, "WP_Settings_Section");
        $this->page = $data['page']->slug;

        $this->settings = [];

        $data['page']->add_section($this);

        add_action('admin_init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    /**
     * Renders the section and any child settings.
     * 
     * @return void
     */
    function render()
    {
        $this->render_children();
    }

    /**
     * Displays all WP_Setting children. Useful for WP_Settings_Section::render().
     * 
     * Can be used directly inside WP_Settings_Page views for customising how settings render on that page.
     * 
     * @see WP_Settings_Section::render()
     * 
     * @return void
     */
    function render_children()
    {
        foreach ($this->settings as $setting) {
            $setting->render();
        }
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
     * Initialises the settings section on the admin_init hook.
     * 
     * @see admin_init
     * @link https://developer.wordpress.org/reference/hooks/admin_init/
     * 
     * @return void
     */
    function init()
    {
        add_settings_section(
            $this->slug,
            $this->title,
            array($this, 'render'),
            $this->page,
        );
    }

    /**
     * Adds a child Option to the internal list.
     * 
     * @param Option $setting Required. The setting to add.
     */
    function add_setting(Option $setting)
    {
        if (!in_array($setting, $this->settings)) {
            $this->settings[$setting->slug] = $setting;
        }
    }
}
