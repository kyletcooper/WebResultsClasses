<?php

namespace wrd;

class Option_Font extends Option
{
    const API_KEY = false;
    const CACHE_LIFESPAN = 604800; // 7 days in seconds



    // Override Methods

    function render_callback()
    {
        $fonts = $this->get_fonts(20);

?>
        <div class='wrd-setting wrd-setting-font' data-font-name="wrd-setting-font-radio-<?php echo uniqid(); ?>" data-page='1' data-current-font="<?php echo esc_attr($this->get_value()); ?>">

            <input type="hidden" name="<?php echo esc_attr($this->slug) ?>" class="wrd-settings-font__current" value="<?php echo esc_attr($this->get_value()); ?>">

            <div class="wrd-setting__title">
                <div>
                    <h3>
                        <?php echo esc_html($this->label) ?>
                    </h3>

                    <div class="wrd-setting-font__preview">
                        <h4 style='font-family: <?php echo esc_attr($this->get_value()); ?>'><?php echo esc_html($this->get_value()); ?></h4>
                        <link rel='stylesheet' href='<?php echo esc_attr($this->get_google_fonts_url($this->get_value())); ?>'>
                    </div>
                </div>
                <span class="wrd-setting__toggle" role="button" tabindex="0">
                    <span class="sr-only"><?php echo __("Expand", 'direct') ?></span>
                </span>
            </div>

            <div class="wrd-setting__reveal">
                <header class='wrd-setting-font__header'>
                    <input type="text" class="wrd-setting-font__search" placeholder="Search Google Fonts...">

                    <nav class='wrd-setting-font__pagination'>
                        <button class="wrd-settings-font__prev wrd-arrow wrd-arrow-left" type="button">
                            <span class="sr-only"><?php echo __("Previous Page", 'direct') ?></span>
                        </button>

                        <div>
                            <?php echo __("Page", 'direct') ?> <span class="wrd-settings-font__page">1</span> <?php echo __("of", 'direct') ?> <span class="wrd-settings-font__total">1</span>
                        </div>

                        <button class="wrd-settings-font__next wrd-arrow wrd-arrow-right" type="button">
                            <span class="sr-only"><?php echo __("Next Page", 'direct') ?></span>
                        </button>
                    </nav>
                </header>


                <div class="wrd-setting-font__results">
                </div>
            </div>
        </div>
<?php
    }

    function enqueue($hook)
    {
        wp_enqueue_style('fontpicker-style', OPTIONS_URL . '/font-picker/style.css', array(), '1.0');
        wp_enqueue_script('fontpicker-script', OPTIONS_URL . '/font-picker/script.js', array(), '1.0');

        wp_localize_script('fontpicker-script', 'WRD_G_FONTS_DATA', $this->get_fonts());
    }



    // Custom Methods

    function get_google_fonts_url($family)
    {
        $url = "https://fonts.googleapis.com/css";

        $data = [
            "display" => "swap",
            "family" => $family,
            "text" => $family,
        ];

        $url = add_query_arg($data, $url);

        return $url;
    }

    function call_api($method, $url, $data = false)
    {
        $curl = curl_init();

        if (!$data['key']) {
            return false;
        }

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    function save_api_cache()
    {
        $response = $this->call_api('GET', 'https://www.googleapis.com/webfonts/v1/webfonts', [
            "key" => $this::API_KEY,
            "sort" => "popularity"
        ]);

        if (!$response) return false;

        $response_array = json_decode($response, true);

        $response_array["lastupdated"] = time();

        file_put_contents(plugin_dir_path(__FILE__) . "font-cache.json", json_encode($response_array));

        return $response_array;
    }

    function get_fonts($count = null, $offset = 0)
    {
        $cache = file_get_contents(plugin_dir_path(__FILE__) . "font-cache.json");
        $cache_array = json_decode($cache, true);

        if (!is_array($cache_array) || !array_key_exists("lastupdated", $cache_array) || time() - $cache_array['lastupdated'] > static::CACHE_LIFESPAN) {
            $new_cache = $this->save_api_cache();
            if ($new_cache) $cache_array = $new_cache;
        }

        $items_sliced = array_slice($cache_array['items'], $offset, $count);

        return $items_sliced;
    }
}
