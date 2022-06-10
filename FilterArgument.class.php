<?php

namespace wrd;

class FilterArgument
{
    protected static $instances = [];

    function __construct(array $opts)
    {
        $this->title    = WRD::array_fallback($opts, "title", __("Filters", 'wrd'));
        $this->slug     = WRD::array_fallback($opts, "slug", sanitize_title($this->title));
        $this->filters  = WRD::array_fallback($opts, "filters", []);
        $this->posttype = WRD::array_fallback($opts, "posttype", ["post"]);

        if (!is_array($this->posttype)) {
            $this->posttype = [$this->posttype];
        }

        static::$instances[] = $this;
    }

    function add_query_args(array $args)
    {
        $metas = [];
        $taxes = [];

        foreach ($this->filters as $filter) {
            if ($filter->is_active()) {
                if ($filter->type == "meta") {
                    $metas[] = $filter->get_query_args();
                } else if ($filter->type == "tax") {
                    $taxes[] = $filter->get_query_args();
                } else {
                    $args[$filter->field] = $filter->get_value();
                }
            }
        }

        if ($metas) {
            $metas["relation"] = "OR";
            $args = static::add_meta_query($args, $metas);
        }

        if ($taxes) {
            $taxes["relation"] = "OR";
            $args = static::add_tax_query($args, $taxes);
        }

        return $args;
    }

    function add_condition($condition_callable)
    {
        $this->condition_callable = $condition_callable;
    }

    function condition()
    {
        if (!property_exists($this, 'condition_callable')) {
            return true;
        }

        return call_user_func($this->condition_callable);
    }

    function render()
    {
?>

        <div class="filter-group">
            <span class="filter-group_title">
                <?php echo esc_html($this->title) ?>
            </span>

            <div class="filter-group_filters">

                <?php

                foreach ($this->filters as $filter) {
                    // Render all children
                    $filter->render();
                }

                ?>

            </div>
        </div>

<?php
    }

    static function add_meta_query($args, $meta)
    {
        if (isset($args['meta_query'])) {
            $args['meta_query'][] = $meta;
        } else {
            $args['meta_query'] = [
                "relation" => "AND",
                $meta
            ];
        }

        return $args;
    }

    static function add_tax_query(array $args, array $tax)
    {
        if (isset($args['tax_query'])) {
            $args['tax_query'][] = $tax;
        } else {
            $args['tax_query'] = [
                "relation" => "AND",
                $tax
            ];
        }

        return $args;
    }

    static function create_from_tax(string $title, string $taxonomy_name)
    {
        $args = [
            "title" => $title,
            "filters" => [],
        ];


        $tax = get_taxonomy($taxonomy_name);
        if ($tax) {
            $args["posttype"] = $tax->object_type;
        }


        $terms = get_terms([
            "taxonomy" => $taxonomy_name,
            "orderby" => "count",
            "order" => "DESC",
            // "hide_empty" => false
        ]);

        if (is_wp_error($terms)) {
            return false;
        }

        foreach ($terms as $term) {
            $args["filters"][] = new Filter([
                "type" => "tax",
                "name" => $term->slug,
                "field" => $taxonomy_name,
                "value" => $term->term_id
            ]);
        }


        return new static($args);
    }

    static function combine(array $args, FilterArgument ...$filter_arguments)
    {
        foreach ($filter_arguments as $group) {
            $args = $group->add_query_args($args);
        }

        return $args;
    }

    static function enqueue()
    {
        global $wp_query;

        $obj = [
            "post_type" => WRD::get_archive_post_types(),
            "paged" => get_query_var("paged", 1),
            "max_num_pages" => $wp_query->max_num_pages,
            "found_posts" => $wp_query->found_posts,

            "ajax_url" => admin_url('admin-ajax.php'),
            "ajax_action" => "filter_posts",
        ];

        if (is_tax()) {
            $obj["term_id"] = get_queried_object_id();
        }

        wp_enqueue_script("filterArgument-js", WRD::dir_to_url() . '/filter-inputs/FilteringSystem.js');
        wp_localize_script("filterArgument-js", "FILTERS", $obj);
    }

    static function get_instances_for_archive($archive_posttypes = null)
    {
        $applicable = [];
        $archive_posttypes = $archive_posttypes ?: WRD::get_archive_post_types();

        if (!is_array($archive_posttypes)) {
            $archive_posttypes = [$archive_posttypes];
        }

        foreach (static::get_instances() as $filterArgument) {
            if (array_intersect($filterArgument->posttype, $archive_posttypes)) {
                $applicable[] = $filterArgument;
            }
        }

        return $applicable;
    }

    static function get_instances()
    {
        return static::$instances;
    }

    static function ajax_filter_posts()
    {
        $page = $_REQUEST["page"] ?: 1;
        $posttype = $_REQUEST["post_type"] ?: "post";

        $archive_filters = FilterArgument::get_instances_for_archive($posttype);

        $args = FilterArgument::combine([
            'paged'     => $page,
            'post_type' => $posttype,
        ], ...$archive_filters);

        if (array_key_exists("term_id", $_REQUEST)) {
            $args = FilterArgument::add_tax_query($args, [
                "field" => "term_id",
                "terms" => $_REQUEST["term_id"]
            ]);
        }

        $query = new \WP_Query($args);

        ob_start();

        foreach ($query->posts as $post) {
            $post = CustomPost::get_post_unknown($post);
            $post->render_preview();
        }

        $html = ob_get_clean();

        WRD::ajax_success([
            "html" => $html,

            "max_num_pages" => $query->max_num_pages,
            "paged" => min($query->max_num_pages, $page)
        ]);
    }
}

add_action("wp_ajax_filter_posts", ["wrd\FilterArgument", "ajax_filter_posts"]);
add_action("wp_ajax_nopriv_filter_posts", ["wrd\FilterArgument", "ajax_filter_posts"]);

add_action('wp_enqueue_scripts', ["wrd\FilterArgument", "enqueue"]);
