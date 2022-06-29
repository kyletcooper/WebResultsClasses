<?php

$posts = get_posts([
    "post_type" => @$this->data['post_type'] ?: "post"
]);

?>

<select id="<?php echo esc_attr($this->id) ?>" name="<?php echo esc_attr($this->key) ?>[]" <?php echo (@$this->data['multiple'] ? "multiple" : "") ?> style="width: calc(100% - 28px);">

    <?php foreach ($posts as $post_choice) :

        $selected = "";

        if (@$this->data['multiple']) {
            $value = $this->get_value($post_id) ? $this->get_value($post_id) : [];
            $selected = in_array($post_choice->ID, $value) ? "selected" : "";
        } else {
            $selected = $post_choice->ID == $this->get_value($post_id) ? "selected" : "";
        }
    ?>

        <option value="<?php echo esc_attr($post_choice->ID) ?>" <?php echo esc_attr($selected) ?>><?php echo esc_attr($post_choice->post_title) ?></option>

    <?php endforeach; ?>

</select>