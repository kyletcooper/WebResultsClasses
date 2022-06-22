<?php

namespace wrd;

define('NAV_DIR', __DIR__ . '/nav/');
define('NAV_URL', WRD::dir_to_url() . '/nav/');

class NavBase extends \Walker_Nav_Menu
{
    static $menu_class = "NavBase";
    static $script = "";
    static $styles = "";


    /**
     * Enquques the styles and script for the nav menu to work.
     */
    static function enqueue()
    {
        add_action("wp_enqueue_scripts", function () {
            $class = get_called_class();

            if (static::$script) {
                wp_enqueue_script($class . "_js", static::$script);
            }

            if (static::$styles) {
                wp_enqueue_style($class . "_css", static::$styles);
            }
        });
    }

    function create_lvl($depth, $args, $list_attrs = [])
    {
        $classes = [
            "sub-menu",
            "sub-menu-depth-$depth",
        ];

        apply_filters('nav_menu_submenu_css_class', $classes, $args, $depth);

        $attrs = [
            "class" => implode(" ", $classes),
        ];

        $attrs = WRD::merge_array_attrs($attrs, $list_attrs);
        $attrs = WRD::array_to_attrs($attrs);

        return "<ul $attrs>";
    }

    function create_li($menu_item, $depth, $args, $param_attrs = []){
        // List Element

        $classes = [
            ...$menu_item->classes,
            "menu-item-$menu_item->ID",
            "menu-item-depth-$depth",
        ];

        $id = apply_filters('nav_menu_item_id', 'menu-item-' . $menu_item->ID, $menu_item, $args, $depth);

        $attrs = [
            "id" => $id,
            "class" => implode(" ", $classes),
        ];

        $attrs = WRD::merge_array_attrs($attrs, $param_attrs);
        $attrs = WRD::array_to_attrs($attrs);

        return "<li $attrs>";
    }

    function create_link($menu_item, $depth, $args, $param_attrs = []){
        // Item Link Element

        $attrs = [
            "href"          => !empty($menu_item->url) ? $menu_item->url : '',
            "title"         => !empty($menu_item->attr_title) ? $menu_item->attr_title : '',
            "target"        => !empty($menu_item->target) ? $menu_item->target : '',
            "class"         => "menu-link menu-link-depth-$depth ",
        ];

        if ($menu_item->current) {
            $attrs['aria-current'] = "page";
        }

        if ('_blank' === $menu_item->target && empty($menu_item->xfn)) {
            $attrs['rel'] = 'noopener';
        } else {
            $attrs['rel'] = $menu_item->xfn;
        }

        if (!$attrs['href'] || $attrs['href'] == '#') {
            $attrs['class'] .= "menu-link-blank ";
        }


        $attrs = apply_filters('nav_menu_link_attributes', $attrs, $menu_item, $args, $depth);
        $title = apply_filters('the_title', $menu_item->title, $menu_item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $menu_item, $args, $depth);

        $attrs = WRD::merge_array_attrs($attrs, $param_attrs);
        $attrs = WRD::array_to_attrs($attrs);

        $item_output  = $args->before;
        $item_output .= "<a $attrs>";
        $item_output .= $args->link_before . $title . $args->link_after;
        $item_output .= '</a>';
        $item_output .= $args->after;

        return apply_filters('walker_nav_menu_start_el', $item_output, $menu_item, $depth, $args);
    }

    function create_el($menu_item, $depth, $args, $list_item_attrs = [], $link_attrs = [])
    {
        $args = apply_filters('nav_menu_item_args', $args, $menu_item, $depth);
        
        return $this->create_li($menu_item, $depth, $args, $list_item_attrs) . $this->create_link($menu_item, $depth, $args, $link_attrs);
    }


    /**
     * Starts the list before the elements are added.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param int      $depth  Depth of menu item. Used for padding.
     * @param stdClass $args   An object of wp_nav_menu() arguments.
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= $this->create_lvl($depth, $args);
    }


    /**
     * Ends the list of after the elements are added.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param int      $depth  Depth of menu item. Used for padding.
     * @param stdClass $args   An object of wp_nav_menu() arguments.
     */
    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= "</ul>";
    }


    /**
     * Starts the element output.
     *
     * @param string   $output            Used to append additional content (passed by reference).
     * @param WP_Post  $data_object       Menu item data object.
     * @param int      $depth             Depth of menu item. Used for padding.
     * @param stdClass $args              An object of wp_nav_menu() arguments.
     * @param int      $current_object_id Optional. ID of the current menu item. Default 0.
     */
    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0)
    {
        $output .= $this->create_el($data_object, $depth, $args);
    }


    /**
     * Ends the element output, if needed.
     *
     * @param string   $output      Used to append additional content (passed by reference).
     * @param WP_Post  $data_object Menu item data object. Not used.
     * @param int      $depth       Depth of page. Not Used.
     * @param stdClass $args        An object of wp_nav_menu() arguments.
     */
    public function end_el(&$output, $data_object, $depth = 0, $args = null)
    {
        $output .= "</li>";
    }
}

add_filter('wp_nav_menu_args', function ($args) {
    if ($args['walker'] && is_subclass_of($args['walker'], "wrd\NavBase")) {
        $args['menu_class'] .= " " . $args['walker']::$menu_class;
    }

    return $args;
});
