<?php

namespace wrd;

wp_enqueue_media();
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

    <form method="post" action="<?php echo esc_attr($this->get_form_action()) ?>" class="wrd-options__form wrd-container">
        <?php if (!function_exists("direct_version")) : ?>
            <output role="alert" class="wrd-alert">
                <h2>
                    The required Direct Theme plugin is not activate.
                </h2>

                <a href="<?php echo admin_url("admin.php?page=tgmpa-install-plugins&plugin_status=activate") ?>">Activate Plugin</a>
            </output>
        <?php endif; ?>

        <?php settings_fields($this->slug); ?>

        <div class="wrd-sections">
            <?php foreach ($this->sections as $section) : ?>

                <div class="wrd-section">

                    <div class="wrd-section__info">

                        <div class="wrd-section__info__sticky">
                            <h2 id="<?php echo esc_attr($section->slug) ?>"><?php echo esc_html($section->title); ?></h2>

                            <p><?php echo esc_html($section->description); ?></p>

                            <input type="submit" name="submit" class="wrd-submit" value="Save Changes">
                        </div>

                    </div>

                    <div class="wrd-section__inputs">
                        <?php $section->render_children(); ?>
                    </div>

                </div>

            <?php endforeach; ?>
        </div>
    </form>
</div>