<?php

namespace wrd;

class Option_Color_Shades extends Option
{
    function render_callback()
    {
?>

        <div class='wrd-setting wrd-color_shades'>

            <div class="wrd-setting__title">
                <div>
                    <h3>
                        <?php echo esc_html($this->label) ?>
                    </h3>
                    <?php if ($this->description) : ?>
                        <p class="wrd-setting__description">
                            <?php echo esc_html($this->description); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <span class="wrd-setting__toggle" role="button" tabindex="0">
                    <span class="sr-only"><?php echo __("Expand", 'direct') ?></span>
                </span>
            </div>

            <div class="wrd-setting__reveal">

                <?php

                foreach ($this->data['shades'] as $shade) {

                    $id = "wrd_color-shades_" . uniqid();
                    $name = $this->slug . "_$shade";
                    $val = get_option($name, $this->default[$shade]);
                    $label = $this->shade_to_name($shade);

                ?>

                    <div class='wrd-color-shades__shade' data-shade='<?php echo esc_attr($shade) ?>'>
                        <input id='<?php echo esc_attr($id) ?>' type='color' name='<?php echo esc_attr($name); ?>' value='<?php echo esc_attr($val) ?>' class='wrd-color-shades__shade__input'>
                        <label for='<?php echo esc_attr($id) ?>' class='wrd-color-shades__shade__label'>
                            <?php echo esc_html($label) ?>
                        </label>
                    </div>

                <?php
                }

                $automaticName = $this->slug . "_automatic";
                $automaticLabel = __("Auto-generate Shades", 'direct');
                $automaticValue = get_option($automaticName, "true");
                $checked = $automaticValue == "true" ? "checked" : "";

                ?>
                <label class='wrd-color-shades__automatic__label'>
                    <input type='checkbox' name='<?php echo esc_attr($automaticName) ?>' value='true' <?php echo esc_attr($checked) ?> class='wrd-color-shades__automatic__input'>
                    <span><?php echo esc_html($automaticLabel) ?></span>
                </label>
            </div>
        </div>

<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('color_shades-style', OPTIONS_URL . '/color-shades/style.css', array(), '1.0');
        wp_enqueue_script('color_shades-script', OPTIONS_URL . '/color-shades/script.js', array(), '1.0');
    }

    function register_setting()
    {
        foreach ($this->data['shades'] as $shade) {
            register_setting($this->page, $this->slug . "_$shade");
        }

        register_setting($this->page, $this->slug . "_automatic");
    }


    // Custom methods
    function shade_to_name($shade)
    {
        $label = "";

        switch ($shade) {
            case 50:
                $label = __("off-white", 'direct');
                break;
            case 100:
                $label = __("near-white", 'direct');
                break;
            case 200:
                $label = __("very light", 'direct');
                break;
            case 300:
                $label = __("light", 'direct');
                break;
            case 400:
                $label = __("lighter", 'direct');
                break;
            case 500:
                $label = __("midtone", 'direct');
                break;
            case 600:
                $label = __("darker", 'direct');
                break;
            case 700:
                $label = __("dark", 'direct');
                break;
            case 800:
                $label = __("very dark", 'direct');
                break;
            case 900:
                $label = __("near-black", 'direct');
                break;
        }

        return $label;
    }
}
