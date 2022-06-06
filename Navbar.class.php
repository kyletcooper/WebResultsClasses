<?php

namespace wrd;

class Navbar
{
    /**
     * @param int|string|WP_Term $menu Required. Menu ID, slug, name, or object.
     */
    function __construct($menu)
    {
        $this->menu = wp_get_nav_menu_object($menu);
        $this->items = wp_get_nav_menu_items($this->menu);
    }

    /**
     * @param string $location Required. Location of the menu.
     */
    static function get_by_location(string $location)
    {
        $locations = get_nav_menu_locations();

        if (!array_key_exists($location, $locations)) {
            return null;
        }

        $menu_id = $locations[$location];
        return new static($menu_id);
    }

    function link_attrs($menu_item)
    {
        $attrs = [];
        $attrs['title']  = !empty($menu_item->attr_title) ? $menu_item->attr_title : '';
        $attrs['target'] = !empty($menu_item->target) ? $menu_item->target : '';
        if ('_blank' === $menu_item->target && empty($menu_item->xfn)) {
            $attrs['rel'] = 'noopener';
        } else {
            $attrs['rel'] = $menu_item->xfn;
        }
        $attrs['href']         = !empty($menu_item->url) ? $menu_item->url : '';
        $attrs['aria-current'] = $menu_item->current ? 'page' : '';

        return WRD::array_to_attrs($attrs);
    }

    function get_children($menu_item)
    {
        $children = [];

        foreach ($this->items as $item) {
            if ($item->menu_item_parent == $menu_item->ID) {
                $children[] = $item;
            }
        }

        return $children;
    }

    function get_levels()
    {
        $levels = [];

        foreach ($this->items as $item) {

            $found = false;

            foreach ($levels as $level) {
                if ($level["parent"] == $item->menu_item_parent) {
                    $found = true;

                    $level["children"][] = $item;
                }
            }

            if (!$found) {
                $levels[] = [
                    "parent" => $item->menu_item_parent,
                    "children" => [$item]
                ];
            }
        }

        return $levels;
    }

    function get_item($id)
    {
        foreach ($this->items as $item) {
            if ($item->ID == $id) {
                return $item;
            }
        }
    }



    function single()
    {
?>

        <nav class="menu menu__single>">
            <ul class="menu-list menu__single-list">

                <?php foreach ($this->items as $item) :
                    if ($item->menu_item_parent != 0) return;
                ?>

                    <li class="menu-item menu__single-item">
                        <a class="menu-link menu__single-link" <?php echo $this->link_attrs($item) ?>>
                            <?php echo $item->title; ?>
                        </a>
                    </li>

                <?php endforeach; ?>

            </ul>
        </nav>

    <?php
    }

    function dropdown()
    {
    ?>

        <nav class="menu menu__dropdown>">
            <ul class="menu-list menu__dropdown-list">

                <?php foreach ($this->items as $item) :
                    if ($item->menu_item_parent != 0) return;
                    $children = $this->get_children($item);
                ?>

                    <li class="menu-item menu__dropdown-item">
                        <a class="menu-link menu__dropdown-link" <?php echo $this->link_attrs($item) ?>>
                            <?php echo $item->title; ?>
                        </a>

                        <?php if ($children) : ?>

                            <ul class="menu-children menu__dropdown-children">
                                <?php foreach ($children as $child) : ?>

                                    <li class="menu-child menu__dropdown-child">
                                        <a class="menu-link menu__dropdown-link" <?php echo $this->link_attrs($child) ?>>
                                            <?php echo $child->title; ?>
                                        </a>
                                    </li>

                                <?php endforeach; ?>
                            </ul>

                        <?php endif; ?>
                    </li>

                <?php endforeach; ?>

            </ul>
        </nav>

    <?php
    }

    function accordion()
    {
    ?>

        <nav class="menu menu__accordion>">
            <ul class="menu-list menu__accordion-list">

                <?php foreach ($this->items as $item) :
                    if ($item->menu_item_parent != 0) return;
                    $children = $this->get_children($item);
                ?>

                    <li class="menu-item menu__accordion-item">
                        <?php if ($children) : ?>

                            <details>
                                <summary>
                                    <?php echo $item->title; ?>
                                </summary>

                                <ul class="menu-children menu__accordion-children">
                                    <?php foreach ($children as $child) : ?>

                                        <li class="menu-child menu__accordion-child">
                                            <a class="menu-link menu__accordion-link" <?php echo $this->link_attrs($child) ?>>
                                                <?php echo $child->title; ?>
                                            </a>
                                        </li>

                                    <?php endforeach; ?>
                                </ul>
                            </details>

                        <?php else : ?>

                            <a class="menu-link menu__accordion-link" <?php echo $this->link_attrs($item) ?>>
                                <?php echo $item->title; ?>
                            </a>

                        <?php endif; ?>
                    </li>

                <?php endforeach; ?>

            </ul>
        </nav>

    <?php
    }

    function panels()
    {
        throw NotImplementedException();
    ?>

        <nav class="menu menu__panels">
            <?php foreach ($this->get_levels() as $level) :
                // $back = $this->get_item($level['back']);
                $current = $this->get_item($level['parent']);
            ?>

                <section class="menu__panel-panel" style="border: 1px solid grey; margin: 2rem" aria-hidden="<?php echo WRD::bool_string($level['parent'] == 0) ?>">

                    <header>

                        <a class="menu__panel-back" data-panel-go="0">
                            Back
                        </a>

                        <h2 class="menu__panel-tite">
                            <?php echo $current ? $current->title : __("Home", 'wrd'); ?>
                        </h2>

                    </header>

                    <ul class="menu-list menu__panel-list">
                        <?php foreach ($level['children'] as $child) : ?>

                            <li class="menu-item menu__panel-item">
                                <a class="menu-link menu__panel-link">
                                    <?php echo $child->title; ?>
                                </a>
                            </li>

                        <?php endforeach; ?>
                    </ul>

                </section>

            <?php endforeach; ?>
        </nav>

<?php
    }
}
