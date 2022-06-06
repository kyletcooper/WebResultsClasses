<?php

namespace wrd;

class Option_Textarea extends Option
{
    // Override Methods

    function render_callback()
    {
?>
        <div class='wrd-setting wrd-setting-textarea'>

            <div class="wrd-setting__title">
                <h3>
                    <?php echo esc_html($this->label) ?>
                </h3>
                <span class="wrd-setting__toggle" role="button" tabindex="0">
                    <span class="sr-only"><?php echo __("Expand", 'direct') ?></span>
                </span>
            </div>

            <div class="wrd-setting__reveal">
                <textarea class="wrd-setting-textarea__input" name="<?php echo esc_attr($this->slug) ?>"><?php echo esc_html($this->get_value()) ?></textarea>
            </div>
        </div>
<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('textarea-style', OPTIONS_URL . '/textarea/style.css', array(), '1.0');
    }
}
