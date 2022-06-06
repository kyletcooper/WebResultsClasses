<?php

namespace wrd;

class Option_Color extends Option
{
    function render_callback()
    {
        $name = $this->slug;
        $type = @$this->data['type'] ?: "text";
        $val = $this->get_value();

?>

        <div class='wrd-setting wrd-setting-color'>

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

                <label class="wrd-setting-color__wrapper">
                    <input class="wrd-setting-color__input" type="color" value="<?php echo esc_attr($val) ?>" type="input" name="<?php echo esc_attr($name) ?>" />
                </label>
            </div>
        </div>

<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('color-style', OPTIONS_URL . '/color/style.css', array(), '1.0');
    }
}
