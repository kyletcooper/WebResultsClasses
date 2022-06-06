<?php

namespace wrd;

class Option_Checkbox extends Option
{
    function render_callback()
    {
        $name = $this->slug;
        $val = $this->get_value();
        $checked = $val == 'true' ? "checked" : "";

?>

        <div class='wrd-setting wrd-setting-checkbox'>

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

                <label class="wrd-setting-checkbox__wrapper">
                    <input class="wrd-setting-checkbox__input" value="true" type="checkbox" name="<?php echo esc_attr($name) ?>" <?php echo esc_attr($checked) ?> />
                    <div class="wrd-setting-checkbox__visual"></div>
                </label>
            </div>
        </div>

<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('checkbox-style', OPTIONS_URL . '/checkbox/style.css', array(), '1.0');
    }
}
