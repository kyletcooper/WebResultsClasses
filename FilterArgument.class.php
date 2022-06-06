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
                    $args['field'] = $filter->get_value();
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
}
