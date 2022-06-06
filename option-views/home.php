<?php

namespace wrd;

wp_enqueue_style('wrd-admin-settings-style', OPTIONSPAGE_URL . 'style.css', array(), '1.0');
wp_enqueue_script('wrd-admin-settings-script', OPTIONSPAGE_URL . 'script.js', array(), '1.0');

?>

<div class="">
    <header class="wrd-hero">
        <div class="wrd-hero-bg">

            <div class="wrd-container">
                <h1 style="margin-bottom: 0px"><?php echo esc_html($this->title) ?></h1>
            </div>

        </div>
    </header>

    <div class="wrd-container">
        <h2>Quick Links</h2>

        <div class="wrd-homegrid">

            <?php foreach ($this->get_subpages() as $page) : ?>
                <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=$page->slug") ?>">
                    <h2><?php echo esc_html($page->title) ?></h2>
                </a>
            <?php endforeach; ?>


            <!-- <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=direct-styling") ?>">
                <h2><?php _e('Styling', 'direct'); ?></h2>

                <p><?php _e('Change the aesthetic of your site globally.', 'direct'); ?></p>
            </a>

            <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=direct-header-footer") ?>">
                <h2><?php _e('Header & Footer', 'direct'); ?></h2>

                <p><?php _e('Edit the appearance of your navigation, footer and masthead.', 'direct'); ?></p>
            </a>

            <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=direct-terms") ?>">
                <h2><?php _e('Default Terms', 'direct'); ?></h2>

                <p><?php _e('Setup sensible default taxonomy terms with one click.', 'direct'); ?></p>
            </a>

            <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=direct-blog") ?>">
                <h2><?php _e('Blog', 'direct'); ?></h2>

                <p><?php _e('Make updates to the look of blog posts and blog archives.', 'direct'); ?></p>
            </a>

            <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=direct-slugs") ?>">
                <h2><?php _e('Permalinks', 'direct'); ?></h2>

                <p><?php _e('Update the URLs for custom pages & listings.', 'direct'); ?></p>
            </a>

            <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=direct-members") ?>">
                <h2><?php _e('Members', 'direct'); ?></h2>

                <p><?php _e('Control options for public users and the content they create.', 'direct'); ?></p>
            </a>

            <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=direct-listings") ?>">
                <h2><?php _e('Listings', 'direct'); ?></h2>

                <p><?php _e('Styling choices for listings and their archive pages.', 'direct'); ?></p>
            </a>

            <a class="wrd-homelink" href="<?php echo admin_url("admin.php?page=direct-extensions") ?>">
                <h2><?php _e('Extensions', 'direct'); ?></h2>

                <p><?php _e('Expand the Direct Theme with add-ons.', 'direct'); ?></p>
            </a> -->

        </div>
    </div>
</div>