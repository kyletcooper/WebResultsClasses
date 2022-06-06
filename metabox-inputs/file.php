<input id="<?php echo esc_attr($this->id) ?>" type="hidden" name="<?php echo esc_attr($this->key) ?>" value="<?php echo esc_attr($this->get_value($post_id)) ?>">

<button class="metabox-file-picker <?php echo ($this->get_value($post_id) != 1 ? "has-media" : ""); ?>" data-mediapicker="<?php echo "#" . $this->id ?>">
    <?php

    if ($this->get_value($post_id) != 1) {
        $url = wp_get_attachment_image_url($this->get_value($post_id));
        $title = get_the_title($this->get_value($post_id));

        echo "<img src='$url'/><span>$title</span>";
    } else {
        _e("Select File", 'direct');
    }

    ?>
</button>

<button class="metabox-file-remove" type="button" data-mediaremove=" <?php echo "#" . $this->id; ?>"><?php _e("Remove image", "direct") ?></button>

<style>
    .metabox-file-picker {
        border: none;
        padding: 0px;
        margin: 0px;
        background: transparent;

        margin-bottom: 10px;

        cursor: pointer;

        color: white;
        font-size: 13px;
        min-height: 32px;
        line-height: 30px;
        border-radius: 3px;
        background-color: rgb(34, 113, 177);
        padding: 0 12px;
    }

    .metabox-file-picker span {
        display: block;
        text-align: left;
    }

    .metabox-file-picker.has-media {
        padding: 0px;
        margin: 0px;
        background: transparent;
        color: #646970;
        line-height: 1.5;

        margin-bottom: 10px;
    }

    .metabox-file-picker img {
        width: 150px;
        display: block;
        margin-bottom: 5px;
    }

    .metabox-file-remove {
        display: block;
        background: transparent;
        border: none;
        padding: 0;
        margin: 0;

        color: #b32d2e;
        text-decoration: underline;
        margin-bottom: 10px;

        cursor: pointer;
    }
</style>

<?php
wp_enqueue_media();
wp_enqueue_script("media-script", get_template_directory_uri() . '/admin/options/inputs/media/script.js', ["jquery", "wp-api"], '1.0');
?>