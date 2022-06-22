<?php

namespace wrd;

class NavDropdown extends NavBase
{
    static $menu_class = "NavDropdown";
    static $script = NAV_URL . "NavDropdown.js";
    static $styles = NAV_URL . "NavDropdown.css";


    /**
     * Starts the list before the elements are added.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param int      $depth  Depth of menu item. Used for padding.
     * @param stdClass $args   An object of wp_nav_menu() arguments.
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $attrs = [
            "data-navdropdown-popup" => true,
            "aria-hidden" => true,
            "class" => "NavDropdown_dropdown",
        ];

        $output .= $this->create_lvl($depth, $args, $attrs);
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
        $attrs = [];

        if ( $this->has_children ) {
            $attrs['aria-haspopup'] = "menu";
            $attrs['data-navdropdown-open'] = true;
            $attrs['class'] = "NavDropdown_opener";
        }
        else if($depth == 0){
            $attrs['data-navdropdown-close'] = true;
            $attrs['class'] = "NavDropdown_closer";
        }

        $output .= $this->create_el($data_object, $depth, $args, $attrs);
    }
}