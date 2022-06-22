<?php

namespace wrd;

class NavFlat extends NavBase
{
    /**
     * Starts the list before the elements are added.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param int      $depth  Depth of menu item. Used for padding.
     * @param stdClass $args   An object of wp_nav_menu() arguments.
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
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
