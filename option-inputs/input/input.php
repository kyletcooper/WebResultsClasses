<?php

namespace wrd;

class Option_Input extends Option
{
    function render_callback()
    {
        $name = $this->slug;
        $type = @$this->data['type'] ?: "text";
        $val = $this->get_value();

?>

        <div class='wrd-setting wrd-setting-input'>

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

                <input class="wrd-setting-input__input" type="<?php echo esc_attr($type); ?>" value="<?php echo esc_attr($val) ?>" type="input" name="<?php echo esc_attr($name) ?>" />
            </div>
        </div>

<?php
    }
}
