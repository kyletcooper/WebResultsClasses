<?php

namespace wrd;

class Option_Button extends Option
{
    function render_callback()
    {
?>

        <div class='wrd-setting wrd-setting-button'>

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

                <button type="submit" name="<?php echo esc_attr($this->data['btn_name']) ?>" value="<?php echo esc_attr($this->default); ?>" class="wrd-setting-button__btn">
                    <?php echo esc_html($this->data['button']) ?>
                </button>
            </div>
        </div>

<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('button-style', OPTIONS_URL . '/button/style.css', array(), '1.0');
    }
}
