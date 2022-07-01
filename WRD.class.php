<?php

namespace wrd;

class WRD
{
    static function dir_to_url(string $path = __DIR__)
    {
        $url = str_replace(
            wp_normalize_path(untrailingslashit(ABSPATH)),
            site_url(),
            wp_normalize_path($path)
        );
        return esc_url_raw($url);
    }
    /**
     * Converts an array of HTML attributes into an escaped string.
     * 
     * Boolean values are converted to strings (e.g. true becomes "true");
     * 
     * @return string $output Escaped HTML attributes string.
     */
    static function array_to_attrs(array $attributes): string
    {
        $output = "";
        foreach ($attributes as $key => $value) {
            if ($value === true) $value = "true";
            if ($value === false) $value = "false";

            $output .= esc_attr($key) . '="' . esc_attr($value) . '" ';
        }

        return $output;
    }

    static function bool_string($bool)
    {
        $bool = (bool) $bool;

        if ($bool) {
            return "true";
        }
        return "false";
    }

    /**
     * Returns true if current query is for this term or one of it's descendents.
     */
    static function is_tax_or_descendent($tax, $term)
    {
        $term = get_term_by("slug", $term, $tax);
        $obj = get_queried_object();

        if (!$term) {
            return false;
        }

        if (!is_tax()) {
            return false;
        }

        if ($obj->slug == $term->slug) {
            return true;
        }

        if (term_is_ancestor_of($term, $obj, $tax)) {
            return true;
        }

        return false;
    }

    static function get_tax_values_array($taxonomy)
    {
        $vals = [];
        $terms = get_terms([
            "taxonomy" => $taxonomy,
            "hide_empty" => false,
            "orderby" => "count",
        ]);

        foreach ($terms as $term) {
            $vals[$term->name] = $term->term_id;
        }

        return $vals;
    }

    static function template_path($base, $name = null)
    {
        $path = $base . '-' . $name . ".php";

        if (!file_exists($path)) {
            $path = $base . ".php";
        }

        if (!file_exists($path)) return false;

        return $path;
    }


    /**
     * Converts an array of IDs into an array of WP_Posts
     */
    static function ids_to_wp_posts($ids)
    {
        $posts = [];

        foreach ($ids as $id) {
            $posts[] = get_post($id);
        }

        return $posts;
    }

    /**
     * Displays a hidden input.
     * 
     * @param string $name Name of the input
     * @param mixed $value Value of the input
     * 
     * @return void
     */
    static function hidden_input(string $name, $value, array $attrs = [])
    {
        $attrs["type"] = "hidden";
        $attrs["name"] = esc_attr($name);
        $attrs["value"] = esc_attr($value);

        $attrs = static::array_to_attrs($attrs);

        echo "<input $attrs />";
    }

    /**
     * Returns an array of terms with a max number.
     * 
     * @param WP_Post|int $post ID or Post Object to get terms for.
     * @param string $taxonomy Taxonomy to get terms of.
     * @param int $max Default 5. The max number of terms to return.
     * 
     * @return array $terms
     */
    static function get_the_terms_limitted($post, string $taxonomy, int $max = 5)
    {
        $terms = get_the_terms($post, $taxonomy);

        if ($terms && !is_wp_error($terms) && count($terms) > $max) {
            $terms = array_slice($terms, 0, $max);
        }

        return $terms;
    }

    /**
     * Checks if all given array keys are in the array and not empty.
     * 
     * @param array $arr Array to search.
     * @param array $keys Keys to search for.
     * 
     * @return bool If all keys are found and not empty.
     */
    static function array_keys_exist(array $arr, array $keys)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $arr)) {
                return false;
            }

            if (empty($arr[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Merges two arrays of HTML attributes. The first array is overriden by the second, except for the class attribute where they are concatenated.
     * 
     * @param array $arr1 The array.
     * @param array $arr2 The overriding array.
     */
    static function merge_array_attrs(array $arr1, array $arr2): array
    {
        $arr = array_merge($arr1, $arr2);

        if (array_key_exists("class", $arr1) && array_key_exists("class", $arr2)) {
            $arr["class"] = $arr1["class"] . " " . $arr2["class"];
        }

        return $arr;
    }

    /**
     * Checks if a value exists in an array and returns it. Otherwise, returns the fallback value.
     * 
     * @param array $arr Array to search.
     * @param string $key Key to search for.
     * @param mixed $fallback Optional. What to provide if the value cannot be found.
     */
    static function array_fallback(array $arr, string $key, $fallback = null)
    {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        }

        return $fallback;
    }

    /**
     * Checks if a string contains a substring in it's length.
     * 
     * @param string $haystack The string to be searched.
     * @param string $need The string to be search for.
     */
    static function str_contains(string $haystack, string $needle): bool
    {
        if (strpos($haystack, $needle) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Converts a string to a URL safe, spaceless string.
     * 
     * @param string $text The string to slugify.
     * @param string $divider The string to replace spaces with. Defaults to _
     * 
     * @return string $text The slugified string or "n-a" if empty.
     */
    static function slugify(string $text, string $divider = "_")
    {
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, $divider);
        $text = preg_replace('~-+~', $divider, $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Returns the length of time it should take to read some text in minutes.
     * 
     * @param string $text The text to read.
     * 
     * @return int $reading_time Reading time in minutes.
     */
    static function reading_length(string $text): int
    {
        $reading_speed = 250; // 250 words per minute.
        $count_words = str_word_count(strip_tags($text));
        $reading_time = ceil($count_words / $reading_speed);

        return $reading_time;
    }

    /**
     * Set the title tag for the current page. Must be run before WP_Head.
     * 
     * @param string $title Title for the page, not including the site signature.
     * 
     * @return void
     */
    static function set_title_tag($title): void
    {
        add_filter('document_title_parts', function ($tag) use ($title) {
            $tag['title'] = $title;
            return $tag;
        });
    }

    /**
     * Add a meta tag to the head of the current page. Must be run before WP_Head.
     * 
     * @param string $name Name of the meta tag.
     * @param string $content Content of the meta tag.
     * 
     * @return void
     */
    static function add_meta_tag($name, $content): void
    {
        add_action("wp_head", function () use ($name, $content) {
            echo "<meta name='$name' content='$content'>";
        });
    }

    /**
     * Redirects to the 404 page and dies.
     * 
     * @return void
     */
    static function redirect_404(): void
    {
        // global $wp_query;
        // $wp_query->set_404();
        // status_header(404);
        get_template_part(404);
        die();
    }

    /**
     * For permission errors. Redirects to the 404 page with a message and dies.
     * 
     * @return void
     */
    static function redirect_403($message = null): void
    {
        // status_header(403);

        get_template_part(404, null, [
            "title" => __("Permission Denied", 'wrd'),
            "body" => $message ?: __("You don't have the proper access to view this page.", 'wrd')
        ]);

        die();
    }

    /**
     * Converts the value to a value between zero and one based on the min and max values.
     * 
     * @param float $val
     * @param float $min Vakye that will be 0.
     * @param float $max Value that will be 1.
     * 
     * @return float $normalized.
     */
    static function normalize(float $val, float $min, float $max)
    {
        return ($val - $min) / ($max - $min);
    }

    /**
     * Returns a HTML link.
     */
    static function create_link($label, $url, $attrs = [])
    {
        $attrs["href"] = $url;
        $attributes = WRD::array_to_attrs($attrs);

        return "<a $attributes>$label</a>";
    }


    /**
     * Adds a new meta query to a WP_Query arguments array.
     * 
     * The new query is appened as an "AND" requirement if there is an existing meta query.
     * 
     * @param array $args Existing arguments.
     * @param array $new_arg New meta query to add.
     * 
     * @return array $args Combined WP_Query args.
     */
    static function add_meta_query_arg(array $args, array $new_arg)
    {
        if (array_key_exists('meta_query', $args) && is_array($args['meta_query'])) {
            $args['meta_query']['relation'] = "AND";
            $args['meta_query'][] = $new_arg;
        } else {
            $args['meta_query'] = [
                $new_arg
            ];
        }

        return $args;
    }

    /**
     * Displays a JSON response for AJAX calls and then kills the script.
     * 
     * @param bool $success If there was an error or not.
     * @param array $data Any other information to send to the client.
     * 
     * @return void
     */
    static function ajax_response(bool $success, array $data = [])
    {
        $data["success"] = $success;
        $data["status"] = $success ? "success" : "error";
        echo json_encode($data);
        wp_die();
    }

    static function ajax_success(array $data = [])
    {
        return static::ajax_response(true, $data);
    }

    static function ajax_error(string $message)
    {
        return static::ajax_response(false, ["message" => $message]);
    }

    /**
     * Displays a background-image style attribute for a given image.
     * 
     * @param int $img ID, WP_Post or Array of IDs for the image.
     * @param string $size Size of the image registered in WordPress.
     * 
     * @return void
     */
    static function image_id_to_background($img, $size = "xlarge")
    {
        if (!$img) {
            return;
        }

        if (is_array($img)) {
            $img = $img[0];
        }

        if (is_a($img, 'WP_Post')) {
            $img = $img->ID;
        }

        $url = wp_get_attachment_image_url($img, $size);

        if (!$url) {
            return;
        }

        echo "style=\"background-image: url('$url')\" ";
    }

    /**
     * Trims a string to a set length with ellipses to indicate it is longer than shown.
     * 
     * @param string $string The text to clip.
     * @param int $length The length to clip to. Defaults to 20.
     * @param string $more The string to add if the text is clipped. Defaults to '...'
     */
    static function trim_chars(string $string, int $length = 20, string $more = '...')
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        $trimmed = substr($string, 0, $length) . $more;
        return $trimmed;
    }

    /**
     * Converts a comma seperated string address into a formatted HTML address.
     * 
     * @param string $address The postal address to format.
     */
    static function format_address(string $address)
    {
        $address = str_replace(",", ",<br/>", $address);

        return $address;
    }

    /**
     * Shortens a formatted HTML address to one line.
     * 
     * @param string $addresss The postal address to format.
     */
    static function address_shorten(string $address)
    {
        if (!$address) return null;

        $address = explode(",", $address);

        $short = $address[0];

        if (strlen($short) < 15) {
            $short .= ", " . $address[1];
        }

        return $short;
    }

    /**
     * Uploads a photo and attaches it to the post.
     * 
     * The causes of errors are reported to the global scope. File not existing is not reported.
     * 
     * @param string $file_hander The index in the $_FILES array for the file.
     * @param int $attachment_post_id ID of the post to attach to. Defaults to 0 (no post).
     * 
     * @return int|false $attachment_ID The ID of the new media post, or false on error.
     */
    static function upload_photo($file_handler, $attachment_post_id = 0)
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        if (!array_key_exists($file_handler, $_FILES)) {
            new ReportableError(ReportableError::SCOPE_GLOBAL, "File not found.", "file_upload");
            return false;
        }

        // File has an error
        if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE   => __('File is too large.', 'wrd'),
                UPLOAD_ERR_FORM_SIZE  => __('File is too large.', 'wrd'),
                UPLOAD_ERR_PARTIAL    => __('File only partially uploaded.', 'wrd'),
                UPLOAD_ERR_NO_FILE    => __('No file found.', 'wrd'),
                UPLOAD_ERR_NO_TMP_DIR => __('No file found.', 'wrd'),
                UPLOAD_ERR_CANT_WRITE => __('File could not be saved.', 'wrd'),
                UPLOAD_ERR_EXTENSION  => __('A PHP extension stopped the file upload', 'wrd'),
            ];

            $error_msg = $error_messages[$_FILES[$file_handler]['error']];
            new ReportableError(ReportableError::SCOPE_GLOBAL, $error_msg, "file_upload");
            return false;
        }

        // Check its an image
        if (!file_is_valid_image($_FILES[$file_handler]["tmp_name"])) {
            new ReportableError(ReportableError::SCOPE_GLOBAL, "File must be an image.", "file_upload");
            return false;
        }

        // Upload!
        $attachment_id = media_handle_upload($file_handler, $attachment_post_id);

        // Check for upload errors.
        if (is_wp_error($attachment_id)) {
            new ReportableError(ReportableError::SCOPE_GLOBAL, $attachment_id->get_error_message(), "file_upload");
            return false;
        }

        return $attachment_id;
    }

    /**
     * Returns the current page for an archive.
     * 
     * @return int $page The current page, defaults to 1.
     */
    function get_pagination()
    {
        return (get_query_var('paged')) ? get_query_var('paged') : 1;
    }

    /**
     * Returns the logo for the site with a link to the home page. Uses the site name if there is no logo.
     * 
     * @param array $attrs Optional. Array of attributes to add the the link.
     * 
     * @return string $logo The HTML of the logo.
     */
    function get_logo(array $attrs = [])
    {
        $custom_logo_id = get_theme_mod('custom_logo');
        $custom_logo_src = wp_get_attachment_image_src($custom_logo_id, 'full');
        $custom_logo = esc_html(get_bloginfo('name'));

        if ($custom_logo_id && $custom_logo_src) {
            $custom_logo_url = $custom_logo_src[0];

            $custom_logo = "<img src='$custom_logo_url' class='h-100' />";
        }

        $default_attrs = [
            "class" => "logo",
            "href" => home_url(),
        ];

        $final_attrs = WRD::array_to_attrs(array_merge(
            $default_attrs,
            $attrs
        ));

        return "<a $final_attrs>$custom_logo</a>";
    }

    /**
     * Checks if all the variables are set and not empty.
     * 
     * @param bool $all Enable to require all variables to match to pass.
     * @param mixed ...$fields All the fields to check.
     * 
     * @return bool If all/at least one variable is set and not empty.
     */
    static function required_fields(bool $all = false, ...$fields)
    {
        foreach ($fields as $field) {
            if (empty($field) && $all) return false;

            if (!empty($field) && !$all) return true;
        }

        return false;
    }

    /**
     * @return string[] Array of posttypes. Defaults to ["post"].
     */
    static function get_archive_post_types()
    {
        $obj = get_queried_object();

        if (is_singular()) {
            return [get_post_type()];
        }
        if (is_post_type_archive()) {
            return [$obj->name];
        }
        if (is_tax()) {
            $tax = get_taxonomy($obj->taxonomy);

            if ($tax) {
                return $tax->object_type;
            }
        }

        return ["post"];
    }

    /**
     * Adds a class/classes to the body element.
     */
    static function add_body_class(string $class)
    {
        add_filter("body_class", function ($classes) use ($class) {
            $classes[] = $class;
            return $classes;
        });
    }

    /**
     * Gets an array containing all the template files as the key and their commented name as the value.
     */
    static function get_part_choices($directory)
    {
        $choices = [];
        $parts = scandir($directory);

        foreach ($parts as $part) {
            if (is_dir($part)) {
                continue;
            }

            $data = get_file_data($directory . DIRECTORY_SEPARATOR . $part, [
                "Part Name" => "Part Name"
            ]);

            $choices[$part] = $data["Part Name"];
        }

        $choices = apply_filters("direct_{$directory}_part_choices", $choices, $directory);

        return $choices;
    }


    static function enqueue()
    {
        wp_enqueue_script("WRD-js", WRD::dir_to_url() . '/query.js');
    }
}

class NotImplementedException extends \BadMethodCallException
{
}
