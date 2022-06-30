<?php

namespace wrd;

class NavPanels extends NavBase
{
    static $menu_class = "NavPanel";
    static $script = NAV_URL . 'NavPanels.js';
    static $styles = NAV_URL . "NavPanels.css";

    public $first = true;

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
            "class" => "navPanel_page",
            "data-navpanel-page" => true,
            "aria-expanded" => false,
        ];

        $output .= $this->create_lvl($depth, $args, $attrs);

        if ($this->previous_el) {
            $back_label   = __("Parent Menu Page", 'wrd');
            $parent_label = $this->previous_el->title;

            $output .= "
            <li class='navPanel_head'>
                <button class='navPanel_back' type='button' data-navpanel-back>$back_label</button>

                <span class='navPanel_title'>
                    $parent_label
                </span>
            </li>
        ";
        }
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
        if ($depth == 0 && $this->first && $args && property_exists($args, 'base_panel_header')) {
            $this->first = false;
            $output .= $args->base_panel_header;
        }

        $output .= $this->create_el($data_object, $depth, $args, [], ["data-navpanel-open" => true]);

        if ($this->has_children) {
            $open_label = __("Open Sub-menu Page", 'wrd');

            $output .= "
                <button class='navPanel_open' type='button' data-navpanel-open>
                    $open_label
                </button>
            ";
        }

        $this->previous_el = $data_object;
    }
}
