<?php

namespace wrd;

class Option_Select extends Option
{
    function render_callback()
    {
        $val = $this->get_value();

?>

        <div class='wrd-setting wrd-setting-select'>

            <div class="wrd-setting__title">
                <div>
                    <h3>
                        <?php echo esc_html($this->label); ?>
                    </h3>


                    <?php if ($this->description) : ?>
                        <p class="wrd-setting__description">
                            <?php echo esc_html($this->description); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <select class="wrd-setting-select__input" name='<?php echo esc_attr($this->slug) ?>'>

                    <?php

                    foreach ($this->data['options'] as $option_key => $option_value) {
                        $selected = $val == $option_key ? "selected" : "";
                        echo "<option $selected value='$option_key'>$option_value</option>";
                    }

                    ?>

                </select>
            </div>
        </div>

<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('select-style', OPTIONS_URL . '/select/style.css', array(), '1.0');
    }
}
