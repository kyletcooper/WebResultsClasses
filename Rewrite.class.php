<?php

namespace wrd;

$WRD_REWRITES = [];

class Rewrite
{
    const REWRITE_QUERY_VAR = "rewrite-name";

    function __construct($opts)
    {
        $default_opts = [
            "name" => "",        // Required
            "type" => "generic", // Used to identify the purpose of a page later, has no impact on behaviour.

            "slug" => "",   // Slug to catch. Can include regex and the 'query_vars' arugment will automatically add them to the rewrite.
            "rewrite" => "index.php", // URL to redirect to. Can generally be left alone unless you're redirecting to one of WordPress' default pages (e.i. 'index.php?post_type=page')
            "query_vars" => [], // Query variables to be added to the write URL and made available to WordPress.

            "template" => "",   // Template to load on the redirect.
            "template_data" => [],  // Data to send to the template.
            "template_callback" => "", // Template function to render the page.
            "template_parameters" => [], // Params to send to template_callback

            "onSubmit" => null, // Function to call if a form on the redirected page is submitted. You should implement nonce/referer checks.
            "onLoad" => null,   // Function to call when the redirected page is loaded.
        ];

        $this->opts = $opts;

        foreach ($default_opts as $opt => $default) {
            $this->$opt = WRD::array_fallback($opts, $opt, $default);
        }

        $this->query_vars[$this->name] = static::REWRITE_QUERY_VAR;
        $this->rewrite = $this->rewrite_add_query_vars($this->rewrite, $this->query_vars);

        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'template_include']);
        add_action('init', [$this, 'rewrite_rule'], 20);

        global $WRD_REWRITES;
        $WRD_REWRITES[] = &$this;
    }

    /**
     * Returns the currently acting rewrite (if one is active).
     * 
     * @return Rewrite $rewrite The rewrite currently affecting the page. Void on no active redirect.
     */
    static function get_active_redirect()
    {
        global $WRD_REWRITES;

        foreach ($WRD_REWRITES as $rewrite) {
            if ($rewrite->is_redirecting()) {
                return $rewrite;
            }
        }
    }

    /**
     * Returns the name of the currently acting rewrite (if one is active).
     * 
     * @return string $name The name of the rewrite object. Void on no active redirect.
     */
    static function get_active_redirect_name()
    {
        $rewrite = static::get_active_redirect();

        if ($rewrite) {
            return $rewrite->get_name();
        }
    }

    /**
     * Returns the type of the currently acting rewrite (if one is active).
     * 
     * @return string $type The type of the rewrite object. Void on no active redirect.
     */
    static function get_active_redirect_type()
    {
        $rewrite = static::get_active_redirect();

        if ($rewrite) {
            return $rewrite->get_type();
        }
    }

    /**
     * Returns the type of a rewrite.
     * 
     * @return string $type
     */
    function get_type()
    {
        return $this->type;
    }

    /**
     * Returns the name of a rewrite.
     * 
     * @return string $name
     */
    function get_name()
    {
        return $this->name;
    }

    /**
     * Checks if the current rewrite should be making any changes.
     * 
     * @return bool $is_redirecting
     */
    function is_redirecting()
    {
        return $this->name == get_query_var(static::REWRITE_QUERY_VAR);
    }

    /**
     * Adds query variables to the rewrite URL, including their REGEX matches index.
     * 
     * @param string $rewrite The rewrite URL to add parameters to.
     * @param array $query_vars Array of query variables to add.
     * 
     * @return string $rewrite The new rewrite URL.
     */
    function rewrite_add_query_vars(string $rewrite, array $query_vars)
    {
        foreach ($query_vars as $i => $query_var) {
            $url = parse_url($rewrite);

            if (array_key_exists("query", $url) && $url['query']) {
                $rewrite .= "&";
            } else {
                $rewrite .= "?";
            }

            if (is_int($i)) {
                $rewrite .= "$query_var=\$matches[" . ($i + 1) . "]";
            } else {
                $i = urlencode($i);
                $rewrite .= "$query_var=$i";
            }
        }

        return $rewrite;
    }

    /**
     * Calls the onSubmit function argument if it exists and the user has submitted a form.
     * 
     * @return void
     */
    function on_submit()
    {
        if (!property_exists($this, 'onSubmit') || !$this->onSubmit) {
            return;
        }

        if (empty($_POST) || !$_SERVER['REQUEST_METHOD'] == 'POST') {
            return;
        }

        call_user_func($this->onSubmit, $this);
    }

    /**
     * Calls the onLoad function argument if it exists.
     * 
     * @return void
     */
    function on_load()
    {
        if (!property_exists($this, 'onLoad') || !$this->onLoad) {
            return;
        }

        call_user_func($this->onLoad, $this);
    }

    /**
     * Filters template tags so they work more easily in user-form.php theme files.
     * 
     * @return void
     */
    private function set_template_data_tags()
    {
        if (array_key_exists('title', $this->template_data)) {
            if (!array_key_exists("content", $this->template_data)) {
                $this->template_data['content'] = "";
            }

            add_filter('the_title', function ($title) {
                if (is_singular() && in_the_loop() && is_main_query()) {
                    return esc_html($this->template_data['title']);
                }

                return $title;
            }, 10, 1);

            add_filter('the_content', function ($content) {
                if (is_singular() && in_the_loop() && is_main_query()) {
                    return wpautop(esc_html($this->template_data['content']));
                }

                return $content;
            }, 10, 1);
        }
    }

    /**
     * Sets the template to the object's template if the user is requesting the redirected page. Also handles triggering callback functions.
     * 
     * @param string $template The inital template to look for.
     * 
     * @return string $template The same template unless the URL is for the redirected page.
     */
    function template_include($template)
    {
        if ($this->is_redirecting()) {
            $this->on_load();
            $this->on_submit();

            $this->set_template_data_tags();

            if ($this->template) {
                $new_template = locate_template($this->template);

                if ('' !== $new_template) {
                    return $new_template;
                }
            }

            if ($this->template_callback) {
                call_user_func($this->template_callback, ...$this->template_parameters);
                return "";
            }
        }

        return $template;
    }

    /**
     * Adds the object's rewrite rule to WordPress. Does not flush the permalinks.
     * 
     * @return void
     */
    function rewrite_rule()
    {
        add_rewrite_rule($this->slug, $this->rewrite, 'top');
    }

    /**
     * Adds required query variables to an array. Used by the 'query_vars' hook.
     * 
     * @param array $query_vars Array to add to.
     * 
     * @return array $query_vars Same array with the new query variables added.
     */
    function add_query_vars($query_vars)
    {
        foreach ($this->query_vars as $var) {
            if (!in_array($var, $query_vars)) {
                $query_vars[] = $var;
            }
        }

        return $query_vars;
    }
}
