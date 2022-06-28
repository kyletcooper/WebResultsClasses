<?php

namespace wrd;

class CustomEditor
{
    const error_scope = "editor_scope";

    private static $instance = null;

    function __construct(CustomPost $post = null)
    {
        $post = CustomPost::get_post_unknown($post);

        $this->post = $post;
        $this->wp_post = $post->post;
        $this->ID = $post->ID;
        $this->class = get_class($post);

        add_action("template_include", '__return_null', PHP_INT_MAX);

        $this->render();
    }

    public static function get_instance()
    {
        if (static::$instance == null) {
            static::$instance = new static();
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

        if (!wp_verify_nonce($postarr['editor_nonce'], 'editor_post-' . $this->ID)) {
            new ReportableError(static::error_scope, __("The form has expired. Please refresh and try again", "wrd"));
            return;
        }

        $update = $this->post->update_post($postarr);

        $this->post = $update;
        $this->wp_post = get_post($this->ID);

        return $update;
    }

    /**
     * Displays the editor page.
     */
    function render()
    {
        if (!CustomUser::current_user_can($this->class::perm_edit, $this->ID)) {
            WRD::redirect_403(__("You don't have permission to edit this post.", "wrd"));
        }

        $this->submit($_POST);

        // Open Page
        WRD::set_title_tag(sprintf(__("Editing %s", "wrd"), $this->wp_post->post_title));
        WRD::add_meta_tag("robots", "noindex");
        get_header();

        echo "<form class='editor' method='post' enctype='multipart/form-data'>";

        wp_nonce_field('editor_post-' . $this->ID, 'editor_nonce');

        do_action("editor_open");
        do_action("editor_{$this->class}_open");

        $this->render_errors();
        $this->render_sections();

        do_action("editor_close");
        do_action("editor_{$this->class}_close");

        echo "</form>";

        get_footer();

        return $this->post;
    }

    /**
     * Displays HTML markup to show all user errors related to the editor.
     * 
     * @return void
     */
    function render_errors()
    {
        $errors = ReportableError::get_by_scope([static::error_scope, ReportableError::SCOPE_GLOBAL]);

        if (!$errors) {
            return;
        }
?>

        <output role="alert" class="editor_errors">
            <ul class="editor_errors_list">
                <?php foreach ($errors as $error) : ?>

                    <li class="editor_error">
                        <?php echo esc_html($error->get_message()) ?>
                    </li>

                <?php endforeach; ?>
            </ul>
        </output>

    <?php
    }

    /**
     * Displays the tabbed regions of fields for the editor.
     * 
     * @return void
     */
    function render_sections()
    {
        $sections = $this->get_sections();

    ?>

        <header class="editor_head">
            <h1 class="editor_title">
                <?php printf(__("Editing %s", 'wrd'), $this->wp_post->post_title) ?>
            </h1>

            <div class="editor_controls">
                <a class="editor_exit" href="<?php echo $this->post->get_permalink() ?>"><?php _e("Close", "wrd") ?></a>
                <button class="editor_submit" type="submit"><?php _e("Save", "wrd") ?></button>
            </div>
        </header>

        <nav class="editor_nav">
            <?php foreach ($sections as $title => $section) : ?>

                <a class="editor_nav_link" href="#<?php echo esc_attr(WRD::slugify($title)) ?>"><?php echo esc_html($title); ?></a>

            <?php endforeach; ?>
        </nav>

        <section class="editor_tabs">
            <?php foreach ($sections as $title => $section) : ?>

                <article class="editor_tab" id="<?php echo esc_attr(WRD::slugify($title)) ?>">

                    <?php

                    foreach ($section as $field) {
                        $field->render($this->ID);
                    }

                    ?>

                </article>

            <?php endforeach; ?>
        </section>
    <?php
    }

    /**
     * Chunks the fields into an arrays where each key is it's own group of related fields.
     * 
     * @return array $sections Array of arrays, e.i. [ "section_name" => [$field1, $field2] ]
     */
    function get_sections()
    {
        $sections = [];

        foreach ($this->post->fields as $field) {
            $sections[$field->get_section()][] = $field;
        }

        return $sections;
    }

    function render_creator()
    {
    ?>
        <style>
            #wpbody {
                position: relative;
            }

            .wrd-cover {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10;

                display: flex;
                align-items: start;
                justify-content: center;

                padding-top: 10%;

                background: #f0f0f1;
            }

            .wrd-cover-container {
                display: flex;
                gap: 1rem;
                flex-direction: column;
                align-items: start;
                justify-content: center;

                padding: 2rem;

                min-width: 30%;

                background: #fff;
                border: 1px solid #CCD8DF;
                border-radius: 8px;
                box-shadow: 0 1rem 1rem rgb(193 232 255 / 20%);
            }

            input.wrd-input,
            select.wrd-input {
                border: 1px solid #CCD8DF;
                border-radius: 4px;

                padding: 0.75rem 1.5rem;
                margin: 0;

                width: 100%;
                min-width: 400px;
            }

            .wrd-btn {
                border: none;
                border-radius: 4px;

                padding: 1rem 2rem;
                margin: 0;

                background: #1E92F8;
                color: white;
                font-weight: 500;

                cursor: pointer;
            }

            .wrd-btn:hover {
                background: #0061B7;
            }
        </style>

        <form class="wrd-cover" action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
            <div class="wrd-cover-container">

                <?php

                foreach ($this->post->get_required_fields() as $field) {
                    $field->render();
                }

                ?>

            </div>
        </form>

<?php
    }
}
