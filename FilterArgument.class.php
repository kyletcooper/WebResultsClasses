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

        // If an archive matches all of these criteria, the filter will be shown.
        // Nulls are ignored.
        $this->author   = WRD::array_fallback($opts, "author", null);
        $this->term     = WRD::array_fallback($opts, "term", null);
        $this->posttype = WRD::array_fallback($opts, "posttype", "post");

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

    function matches_archive_criteria($needle, $haystack){
        if($this->author === null){
            return true;
        }

        if(is_array($haystack) && in_array($needle, $haystack)){
            return true;
        }
        
        if(get_queried_object_id() == $haystack){
            return true;
        }

        return false;
    }

    function can_show_on_archive($archive_type = null, $archive_target = null){
        $obj = WRD::reverse_queried_object($archive_type, $archive_target);
        $id  = WRD::reverse_queried_object_id($archive_type, $archive_target);

        // Check author matches
        if((is_author() || is_a($obj, "WP_User")) && !$this->matches_archive_criteria($id, $this->author)){
            return false;
        }

        // Check post type matches
        if((is_post_type_archive() || is_a($obj, "WP_Post_Type")) && !$this->matches_archive_criteria($obj->name, $this->post_type)){
            return false;
        }

        // Check post type matches
        if((is_tax() || is_a($obj, "WP_Term")) && !$this->matches_archive_criteria($id, $this->term)){
            return false;
        }

        return true;
    }

    function render(){
        ?>

        <div class="filter-group">
            <span class="filter-group_title>
                <?php echo esc_html($this->title) ?>
            </span>

            <div class="filter-group_filters">

                <?php
                
                foreach($this->filters as $filter){
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

        return new static([
            "title" => $title,
            "filters" => $filters
        ]);
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
        ], ...static::get_instances_for_archive());

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
            $obj['query_class'] = get_called_class(get_queried_object());
            $obj['query_id'] = get_queried_object_id();
        }

        ?>
        <script>
            window.archiveFilters = `<?php echo json_encode($obj) ?>`;
        </script>
        <?php
    }

    static function enqueue(){
        wp_enqueue_script("filterArgument-js", WRD::dir_to_url() . '/filter-inputs/filtering.js');
    }

    static function get_instances_for_archive($archive_type = null, $archive_target = null){
        $applicable = [];

        foreach(static::get_instances() as $filterArgument){
            if($filterArgument->can_show_on_archive($archive_type, $archive_target)){
                $applicable[] = $filterArgument;
            }
        }

        return $applicable;
    }

    static function get_instances(){
        return static::$instances;
    }
}

add_action("wp_ajax_filter_posts", ["wrd\FilterArgument", "ajax_filter_posts"]);
add_action("wp_ajax_nopriv_filter_posts", ["wrd\FilterArgument", "ajax_filter_posts"]);

add_action('wp_head', ["wrd\FilterArgument", "js_archive_query"]);

add_action('wp_enqueue_scripts', ["wrd\FilterArgument", "enqueue"]);