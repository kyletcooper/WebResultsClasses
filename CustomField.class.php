<?php

namespace wrd;

class CustomField
{
    // Setup
    public $slug;       // String | Required. The unique identifier of the field.
    public $type;       // String | Optional. Where on the post this field is saved. Allows "meta", "post", "tax" or "attachment". Defaults to "meta".
    public $permission; // String | Optional. The permission level required to edit/view this field in the editor. Defaults to "edit_post".
    public $validation; // Array  | Optional. Array of validation rules. See Validator class.
    public $filtering;  // Array  | Optional. Array of filtering rules. See Validator class.

    // In-Editor Options
    public $label;      // String | Required. The text to describe the input in the editor.
    public $icon;       // String | Optional. Icon to accompany the label.
    public $input;      // String | Optional. Input type to use in the editor. If given a non-standard input type then it will create a custom element. Defaults to "text".
    public $section;    // String | Optional. In tabbed editors, set the fields tab ID.
    public $default;    // String | Optional. Default value if the field is untouched. Defaults to empty string.
    public $values;     // Array  | Optional. Set a range of allowed values if the input is a dropdown. This is not validated against.
    public $attrs;      // Array  | Optional. Array of attributes => values to add to the input.


    // Built-in
    public $opts;

    function __construct($opts)
    {
        $default_opts = [
            "slug" => "",
            "type" => "meta",
            "permission" => "edit_post",

            "label" => "",
            "icon" => "",
            "input" => "text",
            "values" => [],
            "section" => 0,
            "default" => "",
            "attrs" => [],
        ];

        if (array_key_exists('type', $opts) && $opts['type'] == "tax") {
            $default_opts = array_merge($default_opts, [
                "input" => "select",
                "attrs" => [
                    "multiple" => true
                ],
            ]);

            if (array_key_exists('slug', $opts)) {
                $default_opts["values"] = WRD::get_tax_values_array($opts['slug']);
                $default_opts["attrs"]["name"] = $opts['slug'] . "[]";
            }
        }

        foreach ($default_opts as $opt => $default) {
            $this->$opt = WRD::array_fallback($opts, $opt, $default);
        }

        $this->opts = $opts;
    }

    /**
     * Checks if a user has permission to edit this field.
     * 
     * @param int $post_id Required. The post ID to check permissions for.
     * @param WP_User|int $user Optional. User to check. Defaults to current user.
     */
    function user_has_permission($post_id, $user = null)
    {
        if ($this->permission === false) {
            return true;
        }

        $user = new CustomUser($user);
        return $user->has_cap($this->permission, $post_id);
    }

    /**
     * Returns the section for the field.
     * 
     * @return string $section
     */
    function get_section()
    {
        return $this->section;
    }

    /**
     * Returns the slug for the field.
     * 
     * @return string $slug
     */
    function get_slug()
    {
        return $this->slug;
    }

    /**
     * Returns the type for the field.
     * 
     * @return string $type
     */
    function get_type()
    {
        return $this->type;
    }

    /**
     * Returns if the field is required.
     * 
     * @return bool $required
     */
    function is_required()
    {
        return $this->required;
    }

    /**
     * Returns the value of the field for the given post.
     * 
     * @param int $post_id ID of the WP_Post to get the value of.
     * 
     * @return mixed $value The post field, meta field, taxonomy array or attachment ID.
     */
    function get_value(int $post_id)
    {
        $value = null;
        switch ($this->type) {
            case "post":
                $value = get_post_field($this->slug, $post_id);
                break;

            case "tax":
                $value = get_the_terms($post_id, $this->slug);
                break;

            case "attachment":
            case "meta":
                $value = get_post_meta($post_id, $this->slug, true);
                break;
        }

        $value = apply_filters("get_value_{$this->slug}", $value);

        return $value;
    }

    /**
     * Returns the value of the field for a given post, ready to be used in an input HTML element.
     * 
     * @param int $post_id ID of the WP_Post to get the value of.
     * 
     * @return mixed $value The post field, meta field, taxonomy IDs or attachment ID.
     */
    function get_value_output(int $post_id)
    {
        $value = $this->get_value($post_id);

        switch ($this->type) {
            case "tax":
                $value = static::terms_list_to_string($value);
                break;
        }

        return $value;
    }

    /**
     * Updates the value of the field.
     * 
     * @param $post_id
     * @param $value
     * 
     * @return mixed|WP_Error Dependent on field type (or null if no field found). See wp_update_post, wp_set_post_terms or update_post_meta. Error if field was rejected by a filter.
     * 
     * @see https://developer.wordpress.org/reference/functions/wp_update_post/
     * @see https://developer.wordpress.org/reference/functions/wp_set_post_terms/
     * @see https://developer.wordpress.org/reference/functions/update_post_meta/
     */
    function set_value(int $post_id, $value)
    {
        if (!$this->user_has_permission($post_id)) {
            return false;
        }

        /**
         * Filters can return a ReportableError if the value is unrecoverably invalid.
         */
        $value = apply_filters("update_field_{$this->slug}", $value);

        if (ReportableError::is_error($value)) {
            return $value;
        }

        switch ($this->type) {
            case "post":
                return wp_update_post([$this->slug => $value, "ID" => $post_id]);
                break;

            case "tax":
                return wp_set_post_terms($post_id, static::format_terms_input($value), $this->slug);
                break;

            case "attachment":
                return $this->save_attachment($post_id);
                break;

            case "meta":
                return update_post_meta($post_id, $this->slug, $value);
                break;
        }

        return null;
    }

    /**
     * Saves and replaces an attachment file for the field.
     * 
     * @param int $post_id Required. ID of the post to attach the file to.
     * 
     * @return bool True on success, false on failure.
     */
    function save_attachment(int $post_id)
    {
        $file_key = $this->get_slug();

        // No file given
        if (!isset($_FILES[$file_key]) || $_FILES[$file_key]["size"] < 1) {
            return false;
        }

        $attachment_id = WRD::upload_photo($file_key, $post_id);

        if (!$attachment_id) {
            return false;
        }

        // Delete old file for this field
        $old_attachment_id = get_post_meta($post_id, $this->slug, true);
        wp_delete_attachment($old_attachment_id);

        // Set new ID
        update_post_meta($post_id, $this->slug, $attachment_id);
    }

    /**
     * Displays the fields input for the editor (only if the user has permission to edit the field).
     * 
     * @return void
     */
    function render(int $post_id)
    {
        if ($post_id > 0 || !$this->user_has_permission($post_id)) {
            return false;
        }

        $default_inputs = [
            "button",
            "checkbox",
            "color",
            "date",
            "datetime-local",
            "email",
            "file",
            "hidden",
            "image",
            "month",
            "number",
            "password",
            "radio",
            "range",
            "reset",
            "search",
            "submit",
            "tel",
            "text",
            "time",
            "url",
            "week"
        ];

        $option_inputs = [
            "select",
            "datalist"
        ];

        $checked_inputs = [
            "radio",
            "checkbox"
        ];

        $content_inputs = [
            "textarea",
        ];



        $tag = "input";
        $content = "";
        $self_closing = true;


        $attrs = array_merge([
            "class" => "field_input",
            "type" => $this->input,
            "name" => $this->slug,
            "value" => $this->get_value_output($post_id)
        ], $this->attrs);



        if (!in_array($this->input, $default_inputs)) {
            $tag = $this->input;
            $self_closing = false;
        }

        if (in_array($this->input, $option_inputs)) {
            $content = static::array_to_options($this->values, wp_list_pluck($this->get_value($post_id), "term_id"));
        } else if (in_array($this->input, $content_inputs)) {
            $content = $this->get_value_output($post_id);
            unset($attrs['value']);
        } else if (in_array($this->input, $checked_inputs)) {
            if ($this->get_value($post_id)) {
                $attrs["checked"] = true;
            }

            $attrs['value'] = 1;
        }

?>

        <!-- Field -->
        <label <?php $this->field_classes() ?>>
            <span class="field_title">
                <span class="field_icon">
                    <?php echo esc_html($this->icon) ?>
                </span>

                <span class="field_label">
                    <?php echo esc_html($this->label) ?>
                </span>
            </span>

            <span class="field_input_wrapper">
                <?php $this->render_attachment_preview($this->get_value($post_id)); ?>
                <?php static::render_input($tag, $attrs, $self_closing, $content) ?>
            </span>

            <?php if ($this->get_error()) : ?>

                <output class="field_error" role="alert">
                    <?php echo esc_html($this->get_error()); ?>
                </output>

            <?php endif; ?>
        </label>

<?php
    }

    /**
     * Returns the last reported error string for this field.
     * 
     * @return string $error Human readable error message or empty string if no error.
     */
    function get_error()
    {
        $errors = ReportableError::get_by_scope($this->get_slug());

        if (!$errors) {
            return "";
        }

        return $errors[array_key_last($errors)]->get_message();
    }

    /**
     * Displays the class attribute for a field.
     * 
     * @param array $classes Optional. Additional classes to add.
     * 
     * @return void
     */
    function field_classes(array $classes = [])
    {
        $classes = array_merge($classes, [
            "field",
            "feild__$this->input",
            "field__$this->slug"
        ]);

        if ($this->get_error()) {
            $classes[] = "field__has_error";
        }

        $classes = esc_attr(implode(" ", $classes));

        echo " class='$classes' ";
    }

    function render_attachment_preview($attachment_id)
    {
        if (!$attachment_id || is_int($attachment_id) || $this->type !== "attachment") {
            return;
        }

        echo wp_get_attachment_image($attachment_id, 'thumbnail', false, ["class" => "field__attachment_preview"]);
    }

    static function array_to_options(array $options, array $selected = [])
    {
        $out = "";

        foreach ($options as $key => $value) {
            if (is_int($key)) {
                $key = $value;
            }

            $selectAttr = "";
            if (in_array($value, $selected)) {
                $selectAttr = "selected";
            }

            $out .= "<option value='$value' $selectAttr>$key</option>";
        }

        return $out;
    }

    static function wp_post_to_options(array $posts)
    {
        $arr = [];

        foreach ($posts as $post) {
            $arr[$post->post_title] = $post->ID;
        }

        return $arr;
    }

    static function format_terms_input($terms)
    {
        if (is_string($terms)) {
            $terms = explode(",", $terms);
            return array_map('intval', $terms);
        }

        if (is_array($terms)) {
            return array_map('intval', $terms);
        }
    }

    static function terms_list_to_string($terms)
    {
        if (!$terms || !is_array($terms)) {
            return;
        }

        $out = [];

        foreach ($terms as $term) {
            if (!is_a($term, "WP_Term")) {
                continue;
            }

            $out[] = $term->term_id;
        }

        return implode(",", $out);
    }

    /**
     * Displays an input for the editor.
     * 
     * @param string $tag The tag name. Defaults to "input".
     * @param array $attrs The element attributes as an array. Defaults to [].
     * @param bool $self_closing If the element self closes. Defaults to true.
     * 
     * @return void
     */
    static function render_input(string $tag = "input", array $attrs = [], bool $self_closing = true, $content = "")
    {
        $tag = esc_html($tag);
        $attrs = WRD::array_to_attrs($attrs);

        if ($self_closing) {
            echo "<$tag $attrs />";
        } else {
            echo "<$tag $attrs >$content</$tag>";
        }
    }
}
