<?php

namespace wrd;

class FilterArgument
{
    function __construct(string $title, Filter ...$filters)
    {
        $this->title = $title;
        $this->filters = $filters;
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
        $terms = get_terms([
            "taxonomy" => $taxonomy_name,
            "orderby" => "count",
            "order" => "DESC",
            // "hide_empty" => false
        ]);

        $filters = [];

        foreach ($terms as $term) {
            $filters[] = new Filter([
                "type" => "tax",
                "name" => $term->slug,
                "field" => $taxonomy_name,
                "value" => $term->term_id
            ]);
        }

        return new static($title, ...$filters);
    }

    static function combine(array $args, FilterArgument ...$filter_arguments)
    {
        foreach ($filter_arguments as $group) {
            $args = $group->add_query_args($args);
        }

        return $args;
    }

    static function ajax_filter_posts(){
        $page = @$_REQUEST["page"] ?: 1;

        $args = FilterArgument::combine([
            'paged' => $page,
            'post_type' => WRD_LISTING_POSTTYPE
        ], ...Listing::get_filters());

        $query = new \WP_Query($args);

        ob_start();

        var_dump($args);
        var_dump($_REQUEST);

        foreach ($query->posts as $post) {
            $post = CustomPost::get_post_unknown($post);
            $post->render_preview();
        }

        $html = ob_get_clean();

        WRD::ajax_success([
            "listings_html" => $html,

            "max_pages" => $query->max_num_pages,
            "page" => min($query->max_num_pages, $page)
        ]);
    }

    static function js_archive_query(){
        global $wp_query;

        $obj = [
            "post_type" => get_post_type(),
            "page" => get_query_var("paged", 1),
            "max_num_pages" => $wp_query->max_num_pages,
            "found_posts" => $wp_query->found_posts,
        ];

        if (is_archive()) { 
            $queried = get_queried_object();

            if (is_a($queried, 'WP_User')){
                $obj["query_type"] = "user";
                $obj["author_id"] = $queried->ID;
            }
            elseif (is_a($queried, 'WP_Term')){
                $obj["query_type"] = "taxonomy";
                $obj["term_id"] = $queried->term_id;
            }
            elseif (is_a($queried, 'WP_Post_Type')){
                $obj["query_type"] = "post_type";
                $obj["post_type"] = $queried->name;
            }
        }

        ?>
        <script>
            window.archiveFilters = `<?php echo json_encode($obj) ?>`;
        </script>
        <?
    }
}

add_action("wp_ajax_filter_posts", ["wrd\FilterArgument", "ajax_filter_posts"]);
add_action("wp_ajax_nopriv_filter_posts", ["wrd\FilterArgument", "ajax_filter_posts"]);

add_action('wp_head', ["wrd\FilterArgument", "js_archive_query"]);