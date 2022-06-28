<?php

namespace wrd;

class CustomCreator extends CustomEditor
{
    function __construct(string $class = null)
    {
        if (!class_exists($class)) {
            throw new \Exception("Class not found for CustomCreator.");
        }

        $this->class = $class;
        $this->post_type = $class::post_type;
        $this->fields = $this->class::get_fields();
        $this->parent_class = $class::parent_class;
        $this->parent = null;
        $this->exit_link = home_url();
        $this->ID = -1;

        if ($this->parent_class) {
            $this->parent = $this->parent_class::get_post_by_slug(get_query_var("parent"));

            if (!$this->parent) {
                $parent_class = $this->parent_class;

                $possible_parents = get_posts([
                    "post_type" => $parent_class::post_type,
                    "numberposts" => 99
                ]);

                $this->fields[] = new CustomField([
                    "slug" => "post_parent",
                    "type" => "post",

                    "input" => "select",
                    "values" => CustomField::wp_post_to_options($possible_parents),
                    "label" => "Parent",
                    "icon" => "escalator_warning",
                ]);
            } else {
                $parentObj = CustomPost::get_post_unknown($this->parent);
                $this->exit_link = $parentObj->get_permalink();
            }
        }

        if (!CustomUser::current_user_can($this->class::perm_create, $this->class)) {
            WRD::redirect_403(__("You don't have permission to edit this post.", "wrd"));
        }

        $this->render();
    }

    /**
     * Check nonces and saves the changes submitted.
     * 
     * @return void|CustomPost|WP_Error WP_Error if not allowed to update, false if cannot updated,
     */
    function submit(array $postarr)
    {
        if ($this->parent) {
            $postarr["post_parent"] = $this->parent->ID;
        } else {
            $postarr["post_parent"] = 0;
        }

        if (!$postarr["post_title"]) {
            new ReportableError(static::error_scope, __("The title field is required.", "wrd"));
            return;
        }

        $post = $this->class::create_post($postarr["post_title"], $postarr["post_parent"]);

        if ($post) {
            $post->update_post($postarr);
            wp_redirect($post->get_edit_permalink());
        }
    }

    function get_title()
    {
        return sprintf(__("Creating New %s", "wrd"), $this->class::label);
    }
}
