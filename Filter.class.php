<?php

namespace wrd;

class Filter
{
    function __construct(array $data)
    {
        $this->data = $data;

        // Name is used for the URL Query Variables and Input Attribute.
        $this->name = @$data['name'] ?: "";

        // Public facing label for a filter.
        $this->label = @$data['label'] ?: ucwords(str_replace("-", ' ', @$data['name'])) ?: "";

        // Type of query to generate. Allows 'arg', 'tax' or 'meta'.
        $this->type = @$data['type'] ?: "arg";

        // Meta key or Tax key or WP Query Argument
        $this->field = @$data['field'] ?: "s";

        // The value searched for, when default (false) it uses the input value. Useful for checkbox/radio inputs.
        $this->value = @$data['value'] ?: false;

        // Compare type to use when type is 'tax' or 'meta'.
        $this->compare = @$data['compare'] ?: null;

        // Change the input type.
        $this->input = @$data['input'] ?: null;

        // Attributes to give the input
        $this->attrs = @$data['attrs'] ?: [];
    }

    function get_value()
    {
        if ($this->value) {
            return $this->value;
        }

        return @$_REQUEST[$this->name];
    }

    function is_active()
    {
        return @$_REQUEST[$this->name] != null;
    }

    function get_count()
    {
        $posttype = get_post_type();
        $args = ["post_type" => $posttype];

        $query = new \WP_Query($args);

        return $query->found_posts;
    }

    function get_compare()
    {
        global $wp_query;
        $query = clone $wp_query;

        // Add this filter to the query.

        return $this->compare;
    }

    function get_query_args()
    {
        switch ($this->type) {
            case "tax":
                return $this->get_tax_query();
                break;

            case "meta":
                return $this->get_meta_query();
                break;

            default:
                return [[$this->field] => $this->get_value()];
                break;
        }
    }

    function get_tax_query()
    {
        return [
            "taxonomy" => $this->field,
            "terms" => $this->get_value(),
            "operator" => $this->get_compare()
        ];
    }

    function get_meta_query()
    {
        return [
            "key" => $this->field,
            "value" => $this->get_value(),
            "compare" => $this->get_compare()
        ];
    }

    function get_query_args_conditionally()
    {
        if ($this->is_active()) {
            return $this->get_query_args();
        }

        return null;
    }

    function get_input()
    {
        if ($this->value) {
            return "checkbox";
        }

        if (array_key_exists("type", $this->attrs)) {
            return $this->attrs['type'];
        }

        if ($this->input) {
            return $this->input;
        }

        return "text";
    }

    function get_attrs()
    {
        $attrs = WRD::merge_array_attrs([
            "class" => "filter_input",
            "type" => $this->get_input(),
            "name" => $this->name,
            "value" => $this->get_value(),
        ], $this->attrs);

        if ($this->get_input() == "checkbox" || $this->get_input() == "radio") {
            if ($this->is_active()) {
                $attrs["checked"] = true;
            }
        }

        return $attrs;
    }

    function render()
    {
?>
        <label class="<?php echo $this->filter_classes() ?>">
            <span class="field_label">
                <span class="field_label_title">
                    <?php echo esc_html($this->label) ?>
                </span>

                <span class="field_label_count">
                    <?php echo $this->get_count(); ?>
                </span>
            </span>

            <input <?php echo WRD::array_to_attrs($this->get_attrs()) ?>>
        </label>
<?php
    }

    function filter_classes()
    {
        $classes = [
            "filter",
            "filter__" . $this->get_input(),
            "filter__$this->name"
        ];

        if ($this->is_active()) {
            $classes[] = "filter__active";
        }

        $classes = implode(" ", $classes);

        return $classes;
    }
}
