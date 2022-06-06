<?php

namespace wrd;

define("METABOX_DIR", __DIR__ . '/metabox-inputs');
define("METABOX_URL", WRD::dir_to_url() . '/metabox-inputs');

//https://carlalexander.ca/designing-class-wordpress-meta-box/

class Metabox
{
    function __construct($data)
    {
        $this->id = WRD::array_fallback($data, "id", "metabox_" . md5(implode("_", $data)));
        $this->title = WRD::array_fallback($data, "title", __("Metabox", "wrd"));
        $this->description = WRD::array_fallback($data, "description", "");
        $this->template = WRD::array_fallback($data, "template", METABOX_DIR . '/text.php');
        $this->screen = WRD::array_fallback($data, "screen", ["post"]);
        $this->context = WRD::array_fallback($data, "context", "side");
        $this->priority = WRD::array_fallback($data, "priority", "default");
        $this->key = WRD::array_fallback($data, "key", WRD::slugify($this->id));

        $this->data = $data;

        $this->nonce_action = "$this->key\_nonce_save";
        $this->nonce_name = "$this->key\_nonce_value";

        $this->add_hooks();
    }

    function add_hooks()
    {
        add_action('add_meta_boxes', [$this, 'register_metabox']);
        add_action('save_post', [$this, 'save']);
    }

    function register_metabox()
    {
        add_meta_box($this->id, $this->title, [$this, 'render_callback'], $this->screen, $this->context, $this->priority);
    }

    function render_callback($post)
    {
        wp_nonce_field($this->nonce_action, $this->nonce_name);

        echo wpautop($this->description);

        $this->render_template($post->ID);
    }

    function render_template($post_id)
    {
        if (!is_readable($this->template)) {
            return;
        }

        include $this->template;
    }

    function save($post_id)
    {
        if (!isset($_POST[$this->nonce_name])) {
            return $post_id;
        }

        if (!wp_verify_nonce($_POST[$this->nonce_name], $this->nonce_action)) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }

        $this->set_value($post_id, $_POST[$this->key]);
    }

    function get_value(int $post_id)
    {
        return static::get($this->key, $post_id);
    }

    function set_value(int $post_id, $value)
    {
        return static::set($this->key, $value, $post_id);
    }

    static function set(string $key, $value, int $post_id)
    {
        return update_post_meta($post_id, $key, $value);
    }

    static function get(string $key, int $post_id)
    {
        return get_post_meta($post_id, $key, true);
    }
}
