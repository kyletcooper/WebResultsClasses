<?php

namespace wrd;

class Email
{
    const TEMPLATE_DEFAULT = __DIR__ . '/email/default.php';

    function __construct($data)
    {
        $data = array_merge([
            "to" => get_bloginfo('admin_email'),
            "subject" => "New Message from " . get_bloginfo('name'),
            "headers" => [],
            "message" => [],
            "template" => static::TEMPLATE_DEFAULT
        ], $data);

        $this->data = $data;

        $this->to = $data['to'];
        $this->subject = $data['subject'];
        $this->headers = $data['headers'];
        $this->message = $data['message'];
        $this->template = $data['template'];

        $this->success = null;

        $this->send();
    }

    function send()
    {
        add_filter('wp_mail_content_type', [$this, 'set_html_mail_content_type']);
        add_filter('wp_mail_from', [$this, 'set_from']);
        add_filter('wp_mail_from_name', [$this, 'set_from_name']);

        $this->success = wp_mail($this->to, $this->subject, $this->render(), $this->headers);

        // Reset to avoid conflicts.
        remove_filter('wp_mail_content_type', [$this, 'set_html_mail_content_type']);
        remove_filter('wp_mail_from', [$this, 'set_from']);
        remove_filter('wp_mail_from_name', [$this, 'set_from_name']);
    }

    function set_html_mail_content_type()
    {
        return 'text/html';
    }

    function set_from($address)
    {
        $handle = get_theme_option("email_handle", "info");

        return str_replace('wordpress@', "$handle@", $address);
    }

    function set_from_name($name)
    {
        return get_theme_option("email_name", get_bloginfo("name"));
    }

    function render()
    {
        $message = $this->message;
        ob_start();
        include $this->template;
        return ob_get_clean();
    }
}
