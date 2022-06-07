<?php

namespace wrd;

/**
 * CustomPost
 * 
 * This is a rewrite of the Custom Post Types class that makes it more generic
 * and simplifies the options. The code is better documented and features more
 * hooks and actions for extensions to operate on.
 * 
 * At some point we should switch from Custom_Post_Type to CustomPost but there is no
 * set plan for this as of yet. It will likely come at the same time as refactoring
 * the Editor class and implementing Lit Custom Elements for synthetic inputs.
 */

global $REGISTERED_CUSTOMPOSTTYPES;
$REGISTERED_CUSTOMPOSTTYPES = [];

class CustomPost
{
    public $post;
    public $ID;
    public $fields;


    /*  Class Settings  */

    const post_type = null;                 // String. Name of the post type in the database.
    const supports = [                      // Array[String]. Post type supports.
        'title',
        'revisions',
        'thumbnail',
        'custom-fields',
        'author'
    ];

    const icon = "dashicons-star-filled";   // String. Dashicons string for the post type icon.
    const label = "Label";                  // String. Singular name of the post type.
    const label_plural = "Labels";          // String. Plural name of the post type.
    const part = "";                        // String. Style suffix to use for the part.

    const slug = null;                      // String. Prefix before the slug in the URLs.
    const slug_edit_suffix = "edit";        // String. Suffix for the end of URLs to open editor.

    const archive = false;
    const single = false;

    const children_class = [];              // Array. CustomPost classes allowed to be children
    const parent_class = false;             // String. CustomPost Class allowed to be the parent.

    const editor = true;                    // Boolean. Set to false to disable the editor.
    const creator = true;                   // Boolean. Set to false to disable the back-end creator.
    const perm_create = "publish_posts";    // String. The user capability required to create a new post in the front-end.
    const moderate = true;                  // Bolean. Set to false to hide in moderator area.

    const temporary_meta_key = "temporary_post";

    function __construct(\WP_Post $post = null)
    {
        $this->post = $post;
        $this->ID = $post->ID;
        $this->fields = static::get_fields();
    }

    static function get_fields()
    {
        $class = get_called_class();
        return apply_filters("custompost_{$class}_fields", []);
    }

    static function get_filters()
    {
        $class = get_called_class();
        return apply_filters("custompost_{$class}_filters", []);
    }

    static function get_filtered_posts(array $args = [], $useRequest = true){
        $args["paged"] = @$_REQUEST["page"] ?: 1;
        $args["post_type"] = static::post_type;

        // Detect archives
        // filterArchives sents this across.
    
        $args = FilterArgument::combine($args, ...static::get_filters());
    }


    /**
     * Finds the WP Post by either ID, slug, WP_Post or Global Post.
     * 
     * @param WP_Post|Int $post Optional, defaults to global post. The Post to find.
     * 
     * @return static|null $post The post if found, null otherwise.
     */
    static function get_post($post = null)
    {
        $wp_post = get_post($post);

        if (!$wp_post) {
            $wp_post = static::get_post_by_slug($post);
        }

        if ($wp_post && $wp_post->post_type == static::post_type) {
            return new static($wp_post);
        }

        return null;
    }

    /**
     * Returns a post by it's slug.
     * 
     * @param $slug Required. Slug of the post.
     * 
     * @return WP_Post|null The post if found, null otherwise.
     */
    static function get_post_by_slug(string $slug)
    {
        $args = [
            'name'        => $slug,
            'post_type'   => static::post_type,
            'post_status' => 'any',
            'numberposts' => 1
        ];

        $posts = get_posts($args);

        if ($posts) {
            return $posts[0];
        }
    }

    /**
     * Returns an array of WP_Posts that are of the Custom Post Type.
     * 
     * @param array $args Filters for the posts. See WP_Query for more info.
     * 
     * @return array[WP_Post] $posts Array of WP_Posts.
     * 
     * @see https://developer.wordpress.org/reference/classes/wp_query/
     */
    static function get_posts(array $args = [])
    {
        return static::query_posts($args)->posts;
    }

    /**
     * Returns an array of posts of the Custom Post Type class.
     * 
     * @param array $args Filters for the posts. See WP_Query for more info.
     * 
     * @return array[CustomPost] $posts Array of Custom Post Types.
     * 
     * @see https://developer.wordpress.org/reference/classes/wp_query/
     */
    static function get_post_objects(array $args = [])
    {
        $wp_posts = static::get_posts($args);
        $objs = [];

        foreach ($wp_posts as $wp_post) {
            $obj = new static($wp_post);

            if ($obj) {
                $objs[] = $obj;
            }
        }

        return $objs;
    }

    /**
     * Runs a WP_Query for the custom post type.
     * 
     * @param array $args Filters the posts. See WP_Query for more info.
     * 
     * @return WP_Query $query The resulting query.
     * 
     * @see https://developer.wordpress.org/reference/classes/wp_query/
     */
    static function query_posts(array $args = [])
    {
        $args["post_type"] = static::post_type;

        $class = get_called_class();
        $args = apply_filters("pre_get_custom_posts", $args);
        $args = apply_filters("pre_get_custom_{$class}_posts", $args);

        return new \WP_Query($args);
    }

    /**
     * Returns a CustomField for this post type by slug.
     * 
     * @return CustomField|null $field The field or null if not found.
     */
    function get_field(string $slug)
    {
        foreach ($this->fields as $field) {
            if ($slug == $field->get_slug()) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Returns an array of CustomField for this post type by type.
     * 
     * @param string $type The type of the CustomField. See CustomField for values.
     * 
     * @return array[CustomField] The fields of the given type for this post type.
     * 
     * @see CustomField
     */
    function get_fields_by_type(string $type)
    {
        $fields = [];

        foreach ($this->fields as $field) {
            if ($field->get_type() == $type) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Returns the value of the field for this post.
     * 
     * @param string $slug Required. The field's unique slug.
     * 
     * @return mixed $value The value of the field. Defaults to null.
     */
    function get_field_value(string $slug)
    {
        $field = $this->get_field($slug);

        if ($field) {
            return $field->get_value($this->ID);
        }

        return null;
    }

    /**
     * Gets the value of a custom field for the post.
     * 
     * @param string $key The name of the meta field.
     * 
     * @return mixed $value The value of the meta field.
     */
    function get_meta(string $key)
    {
        return get_post_meta($this->ID, $key, true);
    }

    /**
     * Sets the value of a custom field for the post.
     * 
     * @param string $key The name of the meta field.
     * @param string $value The value of the meta field.
     * 
     * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure or if the value passed to the function is the same as the one that is already in the database.
     */
    function set_meta(string $key, $value)
    {
        return update_post_meta($this->ID, $key, $value);
    }

    /**
     * Magic method for getting a fields value.
     * 
     * @see get_field_value()
     */
    function __get($name)
    {
        return $this->get_field_value($name);
    }

    /**
     * Updates the value of the field.
     * 
     * @param string $slug Name of the field to update.
     * @param $value
     * 
     * @return mixed|WP_Error Dependent on field type (or null if no field found). See wp_update_post, wp_set_post_terms or update_post_meta. Error if field was rejected by a filter.
     * 
     * @see https://developer.wordpress.org/reference/functions/wp_update_post/
     * @see https://developer.wordpress.org/reference/functions/wp_set_post_terms/
     * @see https://developer.wordpress.org/reference/functions/update_post_meta/
     */
    function set_field_value(string $slug, $value)
    {
        $field = $this->get_field($slug);

        if ($field) {
            return $field->set_value($this->ID, $value);
        }

        return null;
    }

    /**
     * Updates all the fields in bulk (e.i. for a form submission)
     * 
     * @param array $postarr Array of fields where key is the slug, value is the value.
     * 
     * @return CustomPost $this.
     */
    function update_post(array $postarr)
    {
        $class = get_called_class();
        do_action("custom_post_{$class}_pre_update", $this, $this->ID);

        foreach ($this->fields as $field) {
            if (array_key_exists($field->get_slug(), $postarr)) {
                $field->set_value($this->ID, $postarr[$field->get_slug()]);
            }

            if (array_key_exists($field->get_slug(), $_FILES)) {
                $field->set_value($this->ID, null);
            }
        }

        $this->set_meta(static::temporary_meta_key, false);

        $this->trigger_update();

        return $this;
    }

    /**
     * Fires the update hook for this post.
     * 
     * Useful if updates child content is going to affect the parent's private calculated fields.
     * 
     * @return void
     */
    function trigger_update()
    {
        $class = get_called_class();
        do_action("custompost_{$class}_updated", $this, $this->ID);
    }

    /**
     * Returns the WP_Terms for this post.
     * 
     * @param string $taxonomy The tax to find terms for.
     * @param int $max Caps the number of terms returned. Defaults to -1 (all).
     */
    function get_terms(string $taxonomy, int $max = -1)
    {
        $terms = get_the_terms($this->ID, $taxonomy);

        if (!is_array($terms)) {
            return [];
        }

        if ($max > 0) {
            return array_slice($terms, 0, $max);
        } else {
            return $terms;
        }
    }

    /**
     * For heirarchical posts. Retrieves the parent post object (if it exists).
     * 
     * @return CustomPost|null $parent CustomPost instance of the parent post or null if not found.
     */
    function get_parent()
    {
        if (!static::parent_class) {
            return null;
        }

        $parent_id = get_post_field("post_parent", $this->ID);

        if (!$parent_id) {
            return null;
        }

        return static::get_post_unknown($parent_id);
    }

    /**
     * Sets the parent post.
     * 
     * @param int $parent ID of the parent.
     * 
     * @return bool Success or failure.
     */
    function set_parent(int $parent)
    {
        $parent = static::get_post_unknown($parent);

        if (!$parent || !static::parent_class || !is_a($parent, static::parent_class)) {
            return false;
        }

        $update = wp_update_post([
            "ID" => $this->ID,
            "post_parent" => $parent->ID
        ]);

        if (!$update || is_wp_error($update)) {
            return false;
        }

        return true;
    }

    /**
     * Returns array of WP_Posts of children to this post.
     * 
     * @param string $class Required. The CustomPost class of the posts.
     * @param array $args Optional. Filters the posts. See WP_Query.
     * 
     * @see https://developer.wordpress.org/reference/classes/wp_query/
     */
    function get_children(string $class, array $args = [])
    {
        $query = $this->query_children($class, $args);

        if ($query) {
            return $query->posts;
        }

        return [];
    }

    /**
     * Returns WP_Query of children to this post.
     * 
     * @param string $class Required. The CustomPost class of the posts.
     * @param array $args Optional. Filters the posts. See WP_Query.
     * 
     * @see https://developer.wordpress.org/reference/classes/wp_query/
     */
    function query_children(string $class, array $args = [])
    {
        if (!in_array($class, static::children_class)) {
            return null;
        }

        $args["post_parent"] = $this->ID;
        $args["post_type"] = $class::post_type;

        $class = get_called_class();
        $args = apply_filters("pre_get_custom_post_children", $args);
        $args = apply_filters("pre_get_custom_{$class}_post_children", $args);

        return new \WP_Query($args);
    }

    function get_archive_permalink()
    {
        throw new NotImplementedException();
    }

    function get_permalink()
    {
        if (!static::parent_class || !$this->post->post_parent) {
            return get_the_permalink($this->ID);
        }

        $link = trailingslashit(get_the_permalink($this->post->post_parent));

        if (static::slug) {
            $link .= static::slug . '/';
        }

        return $link . $this->post->post_name . "/";
    }

    function get_edit_permalink()
    {
        return $this->get_permalink() . static::slug_edit_suffix . '/';
    }

    function get_child_archive_permalink(string $class)
    {
        if (!class_exists($class)) {
            throw new \Exception("Class not found ($class).");
        }

        $link = $this->get_permalink();

        if ($class::slug) {
            $link .= $class::slug;
        }

        return $link . '/';
    }

    function get_new_child_permalink(string $class)
    {
        return $this->get_child_archive_permalink($class) . static::slug_edit_suffix . '/';
    }

    /**
     * I do not like this.
     */
    function render_preview(string $style = '', $small = false)
    {
        $posttype = static::post_type;
        $style = static::part;

        global $post;
        $post = $this->post;
        setup_postdata($post);

        $locations = [
            "parts/$posttype/post-$style.php",
            "parts/$posttype/post.php",
            "parts/$posttype-$style.php",
            "parts/$posttype.php",
            "parts/post/$posttype-$style.php",
            "parts/post/$posttype.php",
            "parts/post/post-$style.php",
            "parts/post/post.php",
            "parts/post-$style.php",
            "parts/post.php",
        ];

        if ($small) {
            array_unshift(
                $locations,
                "parts/$posttype/small-$style.php",
                "parts/$posttype/small.php",
            );
        }

        $class = get_called_class();
        $locations = apply_filters("custompost_$class\_parts", $locations);

        locate_template($locations, true, false);

        wp_reset_postdata();
    }

    /**
     * Creates a new post and returns it.
     * 
     * @param string $title Name of the new post.
     * 
     * @return CustomPost|null $post The Custom Post Type object for the new post or null on error.
     */
    static function create_post(string $title, int $parent = null)
    {
        $class = get_called_class();

        if (!CustomUser::current_user_can(static::perm_create)) {
            new ReportableError($class, __("You don't have permission to create posts.", "wrd"));
            return null;
        }

        $ID = wp_insert_post([
            "post_type" => static::post_type,
            "post_title" => sanitize_text_field($title),
            "post_status" => "publish",
        ]);

        if (!$ID || is_wp_error($ID)) {
            new ReportableError($class, $ID->get_error_message());
            return null;
        }

        $post = static::get_post($ID);

        if (!$post) {
            return null;
        }

        if ($parent) {
            $post->set_parent($parent);
        }

        do_action("custompost_created", $post);
        do_action("custompost_{$class}_created", $post);

        return $post;
    }

    /**
     * Registers all the neccessary things to set the post type up. Should be run early on in 'init' hook.
     * 
     * @return void
     */
    static function register()
    {
        $class = get_called_class();

        global $REGISTERED_CUSTOMPOSTTYPES;
        $REGISTERED_CUSTOMPOSTTYPES[static::post_type] = $class;

        add_action('init', function () use ($class) {
            do_action("custompost_pre_register", $class);
            do_action("custompost_{$class}_pre_register", $class);

            static::register_post_type();
            static::register_rewrites();
            static::register_post_create_screen();
            static::register_templates();

            do_action("custompost_registered", $class);
            do_action("custompost_{$class}_registered", $class);
        }, -100);
    }

    static function hook_post_type_link($link, $post)
    {
        if(get_post_type($post) == static::post_type){
            $class = get_called_class();
            $post_obj = static::get_post($post);

            // Temporarily remove this hook to prevent an infinite loop
            remove_filter("post_type_link", [$class, "hook_post_type_link"]);

            $link = $post_obj->get_permalink();

            add_filter("post_type_link", [$class, "hook_post_type_link"], 2);
        }

        return $link;
    }

    static function register_permalink()
    {
        $class = get_called_class();
        add_filter("post_type_link", [$class, "hook_post_type_link"], 2);
    }

    /**
     * Filters the templates when 
     */
    static function register_templates()
    {
        add_filter('template_include', function ($template) {
            $new_template = '';
            $posttype = get_post_type();

            if (static::archive && is_post_type_archive(static::post_type)) {
                $new_template = locate_template([static::archive, "archive-$posttype.php", "archive.php", "index.php"]);
            }

            if (static::single && is_singular(static::post_type)) {
                $new_template = locate_template([static::single, "single-$posttype.php", "single.php", "singular.php", "index.php"]);
            }

            if ('' != $new_template) {
                return $new_template;
            }

            return $template;
        }, 99);
    }

    /**
     * Registers the rewrites for the post.
     * 
     * Currently parent names are not enforced in singular page URLs. Only the slug of the post must be correct, the parent slug is ignored.
     * In archive pages the parent slug is important.
     * 
     * @return void
     */
    static function register_rewrites()
    {
        $class          = get_called_class();
        $archive        = static::slug;
        $placeholder    = "([a-zA-Z0-9-_]+)?";
        $post_type      = static::post_type;
        $edit           = static::slug_edit_suffix;
        $post           = $placeholder;
        $parent         = "";
        $i              = 0;

        $editors = [
            "$archive/$post/$edit" => [$post_type],
        ];

        $creators = [
            "$archive/$edit" => []
        ];

        $archives = [];

        $singles = [];

        if (static::parent_class) {
            $parent_archive = (static::parent_class)::slug;
            $parent_post_type = (static::parent_class)::post_type;
            $parent = "$parent_archive/$placeholder";

            $creators["$parent/$archive/$edit"] = ["parent"];
            $editors["$parent/$archive/$post/$edit"] = ["parent", $post_type];

            $archives["$parent/$archive"] = ["parent"];

            $singles["$parent/$archive/$post"] = ["parent", $post_type];
        }


        if (static::slug_edit_suffix && static::editor) {
            foreach ($editors as $slug => $query_vars) {
                new Rewrite([
                    "name" => "$class-editor-$i",
                    "type" => "editor",
                    "slug" => "$slug/?$",
                    "rewrite" => "index.php?post_type=$post_type",
                    "query_vars" => $query_vars,
                    "template_callback" => ["wrd\CustomEditor", "get_instance"]
                ]);

                $i++;
            }
        }

        if (static::slug_edit_suffix && static::creator) {
            foreach ($creators as $slug => $query_vars) {
                new Rewrite([
                    "name" => "$class-creator-$i",
                    "type" => "creator",
                    "slug" => "$slug/?$",
                    "rewrite" => "index.php?post_type=$post_type",
                    "query_vars" => $query_vars,
                    "template_callback" => ["wrd\CustomCreator", "get_instance"],
                    "template_parameters" => [$class, true]
                ]);

                $i++;
            }
        }

        foreach ($archives as $slug => $query_vars) {
            new Rewrite([
                "name" => "$class-archive-$i",
                "type" => "archive",
                "slug" => "$slug/?$",
                "rewrite" => "index.php?post_type=$post_type",
                "query_vars" => $query_vars
            ]);

            $i++;
        }

        foreach ($singles as $slug => $query_vars) {
            new Rewrite([
                "name" => "$class-single-$i",
                "type" => "single",
                "slug" => "$slug/?$",
                "query_vars" => $query_vars
            ]);

            $i++;
        }
    }

    /**
     * Adds the custom editor to the back end for creating a new post.
     */
    static function register_post_create_screen()
    {
        if (!static::creator) {
            return false;
        }

        $posttype = static::post_type;

        add_action('all_admin_notices', function () {
            $screen = get_current_screen();

            if ($screen->post_type == static::post_type && $screen->action == "add") {
                new CustomCreator(get_called_class());
            }
        });

        add_action("admin_post_create_$posttype", function () {
            $title = $_POST['post_title'];
            $parent = null;

            if (array_key_exists("post_parent", $_POST)) {
                $parent = $_POST['post_parent'];
            }

            $post = static::create_post($title, $parent);
            $url = "";

            if (!$post) {
                $url = add_query_arg([
                    "post_type" => static::post_type,
                ], admin_url('post-new.php'));
            } else {
                $url = $post->get_edit_permalink();
            }

            wp_redirect($url);
            exit();
        });
    }

    /**
     * Registers the post type.
     * 
     * @return void
     */
    static function register_post_type()
    {
        $label = strtolower(static::label);
        $labels = strtolower(static::label_plural);
        $Label = ucwords($label);
        $Labels = ucwords($labels);

        register_post_type(
            static::post_type,
            [
                'labels' => [
                    "name"          => __("$Labels", "wrd"),
                    "singular_name" => __("$Label", "wrd"),
                    "add_new_item"  => __("Add New $Label", "wrd"),
                    "edit_item"  => __("Edit $Label", "wrd"),
                    "new_item"  => __("Save $Label", "wrd"),
                    "view_item" => __("View $Label", "wrd"),
                    "view_items"  => __("View $Labels", "wrd"),
                    "search_items"  => __("Search $Label", "wrd"),
                    "not_found"  => __("No $labels found", "wrd"),
                    "not_found_in_trash "  => __("No $labels found in Trash", "wrd"),
                    "all_items"  => __("All $Labels", "wrd"),
                    "archives"  => __("$Label Archives", "wrd"),
                ],
                'menu_icon' => static::icon,

                'supports' => static::supports,

                'rewrite' => [
                    'slug' => static::slug
                ],

                'public'      => true,
                'hierarchical' => false, // We set this up ourselves
                'has_archive' => true,
                'show_in_rest' => true,
            ]
        );
    }

    /**
     * Get all the CustomPost classes.
     * 
     * @return array $types Array of all CustomPost classes.
     */
    static function get_registered_types()
    {
        global $REGISTERED_CUSTOMPOSTTYPES;
        return $REGISTERED_CUSTOMPOSTTYPES;
    }

    /**
     * Get the CustomPost class of a post type.
     * 
     * @param string $post_type Custom post type to find.
     * 
     * @return string|false $class The class name or false if not found.
     */
    static function post_type_to_class(string $post_type)
    {
        global $REGISTERED_CUSTOMPOSTTYPES;

        if (array_key_exists($post_type, $REGISTERED_CUSTOMPOSTTYPES)) {
            return $REGISTERED_CUSTOMPOSTTYPES[$post_type];
        }

        return false;
    }

    /**
     * Returns the CustomPost of a post who's class is unknown.
     * 
     * @param int|null|WP_Post $post The post to retrieve the CustomPost for. Defaults to global post.
     * 
     * @return CustomPost|null $post The CustomPost of the post. Null if not found or not a valid CustomPost type.
     */
    static function get_post_unknown($post = null)
    {
        $post = get_post($post);

        if (!$post) {
            return null;
        }

        $class = static::post_type_to_class($post->post_type);

        if (!$class) {
            return null;
        }

        return new $class($post);
    }
}
