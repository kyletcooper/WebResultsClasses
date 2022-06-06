<?php

namespace wrd;

class ThemeBase
{
    private static $instance = null;
    public $settings;

    /**
     * Singleton class.
     * This should be instantiated at the very top of functions.php using ThemeBase::get_instance()
     */
    function __construct(array $settings = [])
    {
        $default_settings = [
            "text_domain" => "wrd",
            "lang_dir" => get_template_directory() . '/lang',

            "content_width" => 1536,

            "session" => true,

            "nav_menus" => [
                'nav' => __('Main Navigation', 'wrd'),
                'nav_small' => __('Small Navigation', 'wrd'),
                'footer' => __('Footer Links', 'wrd'),
            ],

            "excerpt_length" => 22,
            "excerpt_more" => "...",
        ];

        $this->settings = array_merge($default_settings, $settings);


        $this->set_content_width();

        add_action('init', [$this, 'init']);
        add_action('after_setup_theme', [$this, 'after_setup_theme']);
        add_action('excerpt_length', [$this, 'excerpt_length']);
        add_action('excerpt_more', [$this, 'excerpt_more']);
        add_filter('wp_get_attachment_image_src', [$this, 'wp_get_attachment_image_src'], 99, 4);
    }

    static function get_instance()
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    function set_content_width()
    {
        global $content_width;
        if (!isset($content_width)) {
            $content_width = $this->settings['content_width'];
        }
    }

    function init()
    {
        if ($this->settings['session'] && !session_id()) {
            session_start();
        }
    }

    function after_setup_theme()
    {
        add_theme_support('editor-styles');
        add_theme_support('custom-logo');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support(
            'html5',
            [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            ]
        );

        load_theme_textdomain($this->settings['text_domain'], $this->settings['lang_dir']);

        register_nav_menus($this->settings['nav_menus']);
    }

    function excerpt_length()
    {
        return $this->settings['excerpt_length'];
    }

    function excerpt_more()
    {
        return $this->settings['excerpt_more'];
    }

    function wp_get_attachment_image_src($image, $attachment = null, $size = null, $icon = null)
    {
        if (!$image) {
            return [
                get_template_directory_uri() . '/assets/img/fallback.png',
                1500,
                1500,
                false
            ];
        }

        return $image;
    }
}
