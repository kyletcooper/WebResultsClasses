<?php

namespace wrd;

class Option_Palette extends Option
{
    function setup($data)
    {
        $this->colors = [
            "bg-color" => __("Background", 'results'),
            "p-color" => __("Paragraphs", 'results'),
            "h-color" => __("Headings", 'results'),
            "btn-color" => __("Button Background", 'results'),
            "btn-text" => __("Button Text", 'results'),
        ];
    }

    function render_callback()
    {
        $top_lvl_id = "wrd-palette_" . uniqid();
        $style = $this->data['style'];
        $palette = [];

        foreach ($this->colors as $slug => $label) {
            $name = $this->data['style'] . "_$slug";
            $val = get_option($name, $this->default[$slug]);
            $id = "wrd-setting-palette_$slug-" . uniqid();

            $palette[$slug] = [
                "name" => $name,
                "val" => $val,
                "label" => $label,
                "id" => $id
            ];
        }

?>

        <div id='<?php echo $top_lvl_id; ?>' class='wrd-setting wrd-setting-palette wrd-setting-uses-palette' data-palette='<?php echo $style; ?>'>

            <div class="wrd-setting__title">
                <h3>
                    <?php echo ucwords($style) . __("Colour Palette", 'results'); ?>
                </h3>
                <span class="wrd-setting__toggle" role="button" tabindex="0">
                    <span class="sr-only">Expand</span>
                </span>
            </div>

            <div class="wrd-setting-palette__pickers">
                <?php foreach ($palette as $c_slug => $c_info) : ?>

                    <div class='wrd-setting-palette__picker'>
                        <input type='color' data-palette-color='<?php echo $c_slug; ?>' data-palette='<?php echo $style; ?>' name='<?php echo $c_info['name'] ?>' class='wrd-setting-palette__input' id='<?php echo $c_info['id'] ?>' value='<?php echo $c_info['val'] ?>' />

                        <label class="wrd-setting-palette__label" for="<?php echo $c_info['id'] ?>" title="<?php echo $c_info['label'] ?>">
                            <div class="sr-only"><?php echo $c_info['label']; ?></div>
                            <div class='wrd-setting-palette__label__color' style="background-color: var(--<?php echo $c_slug ?>);"></div>
                        </label>
                    </div>

                <?php endforeach; ?>
            </div>

            <div class='wrd-setting__reveal'>
                <div class='wrd-setting-palette__example'>
                    <h2>
                        <?php echo __("Example of Colour Scheme", 'results'); ?>
                    </h2>
                    <p>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam sit amet massa sit amet lorem suscipit auctor. Sed lobortis velit quis nunc lobortis vehicula. Aliquam condimentum ullamcorper velit, id blandit sapien. Donec sit amet nisl <a>diam and a link</a>.
                    </p>
                    <a class='btn'>
                        <?php echo __("Register Today", 'results'); ?>
                    </a>
                </div>
            </div>

        </div>

<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('palette-style', OPTIONS_URL . '/admin/inputs/palette/style.css', array(), '1.0');
        wp_enqueue_script('palette-script', OPTIONS_URL . '/admin/inputs/palette/script.js', array('jquery'), '1.0');
    }

    function register_setting()
    {
        foreach ($this->colors as $slug => $label) {
            register_setting($this->page, $this->data['style'] . "_$slug");
        }
    }
}
