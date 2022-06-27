<?php

namespace wrd;

class CustomCreator
{
    const error_scope = "editor_scope";

    private static $instance = null;

    function __construct(string $class = null, bool $all_fields = false)
    {
        if (!class_exists($class)) {
            throw new \Exception("Class not found for CustomCreator.");
        }

        $this->class = $class;
        $this->post_type = $class::post_type;
        $this->parent_class = $class::parent_class;
        $this->parent = null;

        $this->fields = [
            new CustomField([
                "slug" => "post_title",
                "type" => "post",

                "label" => "Title",
                "icon" => "text_fields",
            ])
        ];

        if ($all_fields) {
            $this->fields = $this->class::get_fields();
        }

        if ($this->parent_class) {
            $this->parent = $this->parent_class::get_post_by_slug(get_query_var("parent"));

            if (!$this->parent) {
                $parent_class = $this->parent_class;

                $possible_parents = get_posts([
                    "post_type" => $parent_class::post_type,
                    "numberposts" => 99
                ]);

                $this->fields[] = new CustomField([
                    "slug" => "post_parent",
                    "type" => "post",

                    "input" => "select",
                    "values" => CustomField::wp_post_to_options($possible_parents),
                    "label" => "Parent",
                    "icon" => "escalator_warning",
                ]);
            }
        }

        $this->render();
    }

    public static function get_instance(string $class = null, bool $all_fields = false)
    {
        if (static::$instance == null) {
            static::$instance = new static($class, $all_fields);
        }

        return static::$instance;
    }

    /**
     * Check nonces and saves the changes submitted.
     * 
     * @return void|CustomPost|WP_Error WP_Error if not allowed to update, false if cannot updated,
     */
    function submit(array $postarr)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return; // Form hasn't been submitted.
        }

        if (!wp_verify_nonce($postarr['creator_nonce'], 'creator_post-' . $this->post_type)) {
            new ReportableError(static::error_scope, __("The form has expired. Please refresh and try again", "wrd"));
            return;
        }

        $title = WRD::array_fallback($postarr, "post_title", false);
        $parent = (int) WRD::array_fallback($postarr, "post_parent", 0);

        if (!$title) {
            new ReportableError(static::error_scope, __("The title field is required.", "wrd"));
            return;
        }

        $post = $this->class::create_post($title, $parent);

        if ($post) {
            $post->update_post($postarr);
            wp_redirect($post->get_edit_permalink());
        }
    }

    function render()
    {
        if (!CustomUser::current_user_can("create_content", $this->post_type)) {
            WRD::redirect_403(__("You don't have permission to create this post.", "wrd"));
        }

        $this->submit($_POST);

        // Open Page
        WRD::set_title_tag(sprintf(__("Create New %s", "wrd"), $this->class::label));
        get_header();

        echo "<form class='creator' method='post' enctype='multipart/form-data'>";

        wp_nonce_field('creator_post-' . $this->post_type, 'creator_nonce');

        do_action("creator_open");
        do_action("creator_{$this->class}_open");

        $this->render_form();

        do_action("creator_close");
        do_action("creator_{$this->class}_close");

        echo "</form>";

        get_footer();

        die(); // I don't like this but otherwise there is a bug where the entire archive shows after the footer?
    }

    function render_form()
    {
?>
        <form class="creator-cover" action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
            <div class="creator-modal">

                <?php wp_nonce_field("create_$this->class") ?>
                <input type="hidden" name="action" value="<?php echo "create_$this->post_type" ?>" />

                <?php

                ReportableError::create_list(ReportableError::get_by_scope(static::error_scope));

                foreach ($this->fields as $field) {
                    $field->render(-1);
                }

                if ($this->parent) {
                    WRD::hidden_input("post_parent", $this->parent->ID);
                }

                ?>

                <button class="creator-submit" type="submit">
                    <?php _e("Create", 'wrd'); ?>
                </button>
            </div>
        </form>
<?php
    }
}
