<?php

namespace wrd;

class Metabox_Taxonomy extends Metabox
{
    function __construct($data)
    {
        $this->taxonomy = $data['taxonomy'] ?: "category";

        parent::__construct($data);
    }

    function add_hooks()
    {
        $tax = $this->taxonomy;
        add_action($tax . '_edit_form_fields', [$this, 'render_callback_edit']);
        add_action($tax . '_add_form_fields',   [$this, 'render_callback_new']);

        add_action('edit_' . $tax,   [$this, 'save']);
        add_action('create_' . $tax, [$this, 'save']);
    }

    function render_callback_edit($term)
    {
        wp_nonce_field($this->nonce_action, $this->nonce_name);
?>

        <tr class="form-field term-meta-text-wrap">
            <th scope="row">
                <label for="<?php echo esc_attr($this->id) ?>"><?php echo esc_html($this->title); ?></label>
            </th>
            <td>
                <?php $this->render_template($term->term_id); ?>
                <p class="description">
                    <?php echo esc_html($this->description); ?>
                </p>
            </td>
        </tr>

    <?php
    }

    function render_callback_new()
    {
        wp_nonce_field($this->nonce_action, $this->nonce_name);

    ?>

        <div class="form-field term-meta-text-wrap">
            <label for="<?php echo esc_attr($this->id) ?>"><?php echo esc_html($this->title); ?></label>
            <?php $this->render_template(0); ?>
            <div>
                <?php echo wpautop($this->description); ?>
            </div>
        </div>

<?php
    }


    static function set(string $key, $value, int $term_id)
    {
        return update_term_meta($term_id, $key, $value);
    }

    static function get(string $key, int $term_id)
    {
        return get_term_meta($term_id, $key, true);
    }
}
