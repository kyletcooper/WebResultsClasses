<?php

namespace wrd;

wp_enqueue_style('wrd-admin-settings-style', OPTIONSPAGE_URL . 'style.css', array(), '1.0');
wp_enqueue_script('wrd-admin-settings-script', OPTIONSPAGE_URL . 'script.js', array(), '1.0');

?>

<div class="">
    <header class="wrd-hero">
        <div class="wrd-hero-bg">

            <div class="wrd-container">

                <h1><?php echo __("Direct Theme", 'direct') ?></h1>
                <h2><?php echo __("Created by <a href='https://webresultsdirect.com' target='_blank'>Web Results Direct</a>", 'direct'); ?></h2>

            </div>

        </div>
    </header>

    <div class="wrd-container">
        <h2>Extensions</h2>

        <div class="wrd-rows">

            <?php foreach (ThemeExtension::get_all() as $extension) : ?>

                <div class="wrd-extension">
                    <h2 class="wrd-row">
                        <div class="wrd-activedot <?php echo esc_attr($extension->get_status()) ?>"></div>
                        <?php echo esc_html($extension->title) ?>
                    </h2>

                    <div>
                        <?php echo wpautop(strip_tags($extension->description)); ?>
                    </div>

                    <div class="wrd-row wrd-row-gap">

                        <?php if ($extension->get_status() == ThemeExtension::ACTIVE) : ?>
                            <a href="<?php echo esc_attr($extension->settings_page) ?>"><?php _e("Extension Settings", 'direct'); ?></a>
                        <?php endif; ?>

                        <?php if ($extension->author_uri && $extension->author_name) : ?>
                            <a href="<?php echo esc_attr($extension->author_uri) ?>"><?php echo esc_html($extension->author_name) ?></a>
                        <?php endif; ?>

                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </div>