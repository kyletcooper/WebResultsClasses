<?php

namespace wrd;

class NavAccordion extends NavBase
{
    static $menu_class = "NavAccordion";
    static $script = NAV_URL . "NavAccordion.js";
    static $styles = NAV_URL . "NavAccordion.css";


    /**
     * Starts the list before the elements are added.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param int      $depth  Depth of menu item. Used for padding.
     * @param stdClass $args   An object of wp_nav_menu() arguments.
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        // Panel
        $attrs = [
            "id" => "NavAccordion_panel_" . $this->previous_el->ID,
            "class" => "NavAccordion_panel",

            "data-navaccordion-panel" => true,

            "aria-labelledby" => "NavAccordion_btn_" . $this->previous_el->ID,
            "aria-hidden" => true,
            "role" => "region",
            "inert" => true
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

        if ($this->has_children) {
            $attrs['aria-expanded'] = false;
            $attrs['aria-controls'] = true;
            $attrs['class'] = "NavAccordion_opener";

            $output .= $this->create_li($data_object, $depth, $args, $attrs);
            $output .= "<div class='NavAccordion_header' data-navaccordion-header>";
            $output .= $this->create_link($data_object, $depth, $args);


            // Button
            $btn_label = __("Expand", 'wrd');

            $btn_attrs = [
                "id" => "NavAccordion_btn_" . $data_object->ID,
                "class" => "NavAccordion_btn",

                "data-navaccordion-btn" => true,

                "aria-label" => $btn_label,
                "aria-controls" => "NavAccordion_panel_" . $data_object->ID,
                "aria-expanded" => false,
                "role" => "button"
            ];

            $btn_attrs = WRD::array_to_attrs($btn_attrs);

            $output .= "<button $btn_attrs>$btn_label</button>";
            $output .= "</div>";
        } else {
            $output .= $this->create_el($data_object, $depth, $args);
        }


        $this->previous_el = $data_object;
    }
}
