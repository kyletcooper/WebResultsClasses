<?php

namespace wrd;

define('OPTIONSPAGE_DIR', __DIR__ . '/option-views/');
define('OPTIONSPAGE_URL', WRD::dir_to_url() . '/option-views/');

/**
 * Sets up and manages a WordPress admin setting page. Contains a set of WP_Settings_Section objects.
 *
 */
class OptionPage
{

    /**
     * Create a WP_Settings_Page instance.
     * 
     * @param array $data{
     *      Required. Array of options for the page.
     * 
     *      @type string $title         Required. Acts as the label and slug.
     *      @type string $template      Required. Path to a PHP template that renders all sections/settings fields.
     * }
     */
    function __construct($data)
    {
        $this->title = $data['title'];
        $this->template = @$data['template'] ?: OPTIONSPAGE_DIR . 'settings.php';
        $this->icon = @$data['icon'] ?: null;
        $this->on_save = @$data['on_save'] ?: null;
        $this->on_submit = @$data['on_submit'] ?: null;
        $this->parent = @$data['parent'] ?: null;
        $this->position = @$data['position'] ?: 3;
        $this->data = $data;

        $this->sections = [];

        $this->slug = $data['slug'] ?: Option::setting_slug($this->title, "WP_Settings_Page");

        $this->capabilities = 'manage_options';

        if (is_a($this->parent, "wrd\OptionPage")) {
            $this->parent = $this->parent->slug;
        }

        global $WRD_SETTINGS_PAGES;
        $WRD_SETTINGS_PAGES[] = $this;

        add_action('admin_menu', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
        add_action('admin_bar_menu', array($this, 'adminbar'), 80);

        add_action('admin_init', array($this, 'on_save'), 999, 0);
        add_action('admin_init', array($this, 'on_submit'), 999, 0);
    }

    /**
     * Displays the page template.
     * 
     * The page template is in charge of displaying the sections/setting fields. The template can use $this or $page to reference this object.
     * The sections for the page can be accessed using $this->sections.
     * 
     * @return void
     */
    function render()
    {
        $page = $this;
        include $this->template;
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
     * Initialises the setting field on the admin_init hook.
     * 
     * @see admin_init
     * @link https://developer.wordpress.org/reference/hooks/admin_init/
     * 
     * @return void
     */
    function init()
    {
        if ($this->parent) {
            add_submenu_page(
                $this->parent,
                $this->title,
                $this->title,
                $this->capabilities,
                $this->slug,
                array($this, 'render'),
                $this->position,
            );
        } else {
            add_menu_page(
                $this->title,
                $this->title,
                $this->capabilities,
                $this->slug,
                array($this, 'render'),
                $this->icon,
                $this->position,
            );
        }
    }

    /**
     * Adds the menu to the adminbar on the admin_bar_menu hook.
     * 
     * @see admin_bar_menu
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     * 
     * @return void
     */
    function adminbar($admin_bar)
    {
        if (array_key_exists("adminbar", $this->data) && $this->data['adminbar']) {
            $admin_bar->add_menu([
                'id'    => $this->slug,
                'title' => $this->title,
                'href'  => get_admin_url(null, 'admin.php?page=' . $this->slug),
            ]);
        }
    }

    /**
     * Adds a child WP_Settings_Section to the internal list.
     * 
     * @param WP_Settings_Section $setting Required. The settings section to add.
     */
    function add_section(OptionSection $section)
    {
        if (!in_array($section, $this->sections)) {
            $this->sections[$section->slug] = $section;
        }
    }

    /**
     * 
     */
    function on_save()
    {
        if (
            !(array_key_exists('page', $_REQUEST) &&
                $_REQUEST['page'] == $this->slug &&
                array_key_exists('settings-updated', $_REQUEST) &&
                $_REQUEST['settings-updated'] == true)
        ) {
            return false;
        }

        if ($this->on_save) {
            call_user_func($this->on_save);
        }
    }

    /**
     * 
     */
    function on_submit()
    {
        if (
            !(array_key_exists('option_page', $_REQUEST) &&
                $_REQUEST['option_page'] == $this->slug &&
                !empty($_POST))
        ) {
            return false;
        }

        if ($this->on_submit) {
            call_user_func($this->on_submit);
        }
    }

    /**
     * Retrieves where the form should submit to. Defaults to WordPress' options page for automatic saving.
     */
    function get_form_action()
    {
        if ($this->on_submit === null) {
            return "options.php";
        }
    }

    /**
     * Returns array of WP_Settings_Pages that are attached to this one.
     */
    function get_subpages()
    {
        global $WRD_SETTINGS_PAGES;
        $subpages = [];

        foreach ($WRD_SETTINGS_PAGES as $page) {
            if ($page->parent == $this->slug) {
                $subpages[] = $page;
            }
        }

        usort($subpages, function ($a, $b) {
            return $a->position <=> $b->position;
        });

        return $subpages;
    }

    static function exists($slug){
        global $WRD_SETTINGS_PAGES;

        if(!$WRD_SETTINGS_PAGES){
            $WRD_SETTINGS_PAGES = [];
        }

        foreach($WRD_SETTINGS_PAGES as $page){
            if($page->slug == $slug){
                return true;
            }
        }

        return false;
    }
}
