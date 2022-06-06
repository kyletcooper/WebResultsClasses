<?php

namespace wrd;

class Option_Font_Weight extends Option
{
    function render_callback()
    {
        $val = $this->get_value();

        $font_weights = [
            "100",
            "200",
            "300",
            "400",
            "500",
            "600",
            "700",
            "800",
            "900",
        ];

?>

        <div class='wrd-setting wrd-setting-font-weight'>

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

                <div class="wrd-setting-font-weight__options">

                    <?php

                    foreach ($font_weights as $weight) {
                        $selected = $val == $weight ? "checked" : "";
                        echo "
                        <label style='font-weight:$weight;' class='wrd-setting-font-weight__option'>
                            <input type='radio' name='$this->slug' $selected value='$weight'/>
                            <span>$weight</span>
                        </label>";
                    }

                    ?>

                </div>
            </div>
        </div>

<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('font-weight-style', OPTIONS_URL . '/font-weight/style.css', array(), '1.0');
    }
}
