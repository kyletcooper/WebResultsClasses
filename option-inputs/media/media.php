<?php

namespace wrd;

class Option_Media extends Option
{
    function render_callback()
    {
        $name = $this->slug;
        $val = $this->get_value();
        $id = uniqid("mediaSetting_");
?>

        <div class='wrd-setting wrd-setting-media'>

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

                <label class="wrd-setting-media__wrapper">
                    <input id="<?php echo esc_attr($id); ?>" type="hidden" value="<?php echo esc_attr($val) ?>" name="<?php echo esc_attr($name) ?>">

                    <button class="wrd-setting-media__button <?php echo ($this->get_value() ? "has-media" : null); ?>" type="button" aria-label="<?php _e("Select File", "direct") ?>" data-mediapicker="<?php echo esc_attr("#$id"); ?>">
                        <?php

                        if ($this->get_value()) {
                            $url = esc_attr(wp_get_attachment_image_url($this->get_value()));
                            $title = esc_html(get_the_title($this->get_value()));

                            echo "<img src='$url'/><span>$title</span>";
                        } else {
                            _e("Select File", 'direct');
                        }

                        ?>
                    </button>

                    <button class="wrd-setting-media__remove" type="button" data-mediaremove="#<?php echo esc_attr($id); ?>" aria-label="<?php _e("Remove", "direct") ?>">&times;</button>
                </label>
            </div>
        </div>

<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('media-style', OPTIONS_URL . '/media/style.css', array(), '1.0');
        wp_enqueue_script('media-script', OPTIONS_URL . '/media/script.js', array(), '1.0');
    }
}
