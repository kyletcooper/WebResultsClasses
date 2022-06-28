<?php

namespace wrd;

/**
 *  Much of what we need here is handled by WordPress such as login.
 * 
 *  Forms are not implemented here.
 */

class CustomUser
{
    const ERROR_SCOPE = "CustomUser";

    const VERIFIED_META_KEY = "email_verified";
    const REQUIRED_FIELDS = ["display_name", "user_pass", "user_email"];

    function __construct($user = null)
    {
        if (is_int($user)) {
            $user = get_user_by("ID", $user);
        }

        if (is_string($user)) {
            $user = get_user_by("login", $user);
        }

        if (is_null($user)) {
            $user = wp_get_current_user();
        }

        if (!$user) {
            throw new \Exception("User not found.");
        }

        $this->user = $user;
        $this->ID = $user->ID;
    }

    /**
     * Returns the CustomUser object for the currently logged in user.
     */
    static function current()
    {
        return new CustomUser(wp_get_current_user());
    }

    /**
     * Creates a new user with custom validation.
     * 
     * "user_login" is auto-generated based on "display_name". "display_name", "user_pass" & "user_email" are required.
     * 
     * @param array $userarr User data. See wp_insert_user()
     * 
     * @return CustomUser|false The new user's object on success. False on failure.
     * 
     * @see https://developer.wordpress.org/reference/functions/wp_insert_user/
     */
    static function create_user(array $userarr)
    {
        $userarr = static::validate_user_data($userarr);

        if (!$userarr) {
            return false;
        }

        // Required fields
        if (!WRD::array_keys_exist($userarr, static::REQUIRED_FIELDS)) {
            new ReportableError(static::ERROR_SCOPE, __("Please ensure all fields are filled in.", "wrd"));
            return false;
        }

        if (array_key_exists("ID", $userarr)) {
            unset($userarr["ID"]);
        }

        // Forced fields
        $userarr["user_login"] = static::generate_username($userarr['display_name']);
        $userarr["show_admin_bar_front"] = 'false';
        $userarr["role"] = "subscriber";

        $user_id = wp_insert_user($userarr);

        if (is_wp_error($user_id)) {
            new ReportableError(static::ERROR_SCOPE, $user_id->get_error_message());
            return false;
        }

        $user = new static($user_id);

        $user->send_verify_email();

        return $user;
    }

    /**
     * Checks if the given email is in use by a user.
     * 
     * @param string $email The email address to check against.
     * 
     * @return bool $taken True if the email address is in use, otherwise false.
     */
    static function email_taken(string $email): bool
    {
        $user = get_user_by("email", $email);

        if ($user) {
            return true;
        }

        return false;
    }

    /**
     * Checks a passwords strength and returns true if it is acceptable.
     * 
     * @param string $password Password to check.
     * 
     * @return bool $strong If the password is strong or not.
     */
    static function password_strength(string $password)
    {
        $uppercase    = preg_match('@[A-Z]@', $password);
        $lowercase    = preg_match('@[a-z]@', $password);
        $number       = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
            return false;
        }

        return true;
    }

    static function validate_user_data(array $userarr)
    {
        if (array_key_exists("user_pass", $userarr)) {
            // Password too weak
            if (!static::password_strength($userarr['user_pass'])) {
                new ReportableError(static::ERROR_SCOPE, __("Passwords must at least 8 characters long and contain one uppercase, lowercase, number and special character.", 'wrd'));
                return false;
            }

            if (!array_key_exists("confirm_pass", $userarr) || $userarr['user_pass'] !== $userarr['confirm_pass']) {
                new ReportableError(static::ERROR_SCOPE, __("Your passwords do not match.", 'wrd'));
                return false;
            }
        }

        if (array_key_exists("user_email", $userarr)) {
            // Email taken
            if (static::email_taken($userarr['user_email'])) {
                new ReportableError(static::ERROR_SCOPE, __("This email is already in use. Please try logging in.", "wrd"));
                return false;
            }

            // Email invalid
            if (!filter_var($userarr['user_email'], FILTER_VALIDATE_EMAIL)) {
                new ReportableError(static::ERROR_SCOPE, __("This email is not valid. Please check it and retry.", "wrd"));
                return false;
            }
        }

        return $userarr;
    }

    /**
     * Generates a unique user login from a name string.
     * 
     * @param string $name The display name.
     * 
     * @return string $login The unique login.
     */
    static function generate_username(string $name)
    {
        $name = WRD::slugify($name);
        $i = 0;
        $username = $name;

        $username_taken = get_user_by("login", $name);

        while ($username_taken) {
            $i++;
            $username = $name . "_$i";

            $username_taken = get_user_by("login", $name);
        }

        return $name;
    }

    /**
     * Checks if the current user has permission to do an action.
     * 
     * @param $capability Name of the capability.
     * @param $args Arguements to pass. Normally the post ID.
     * 
     * @return bool $granted If the user has permission.
     */
    static function current_user_can(string $capability, ...$args)
    {
        $user = new CustomUser();
        return $user->has_cap($capability, ...$args);
    }

    /**
     * Updates the current user. For fields see wp_insert_user()
     * 
     * @param array $userarr Updated values for the user.
     * @param bool $require_permission If permission should be checked.
     * 
     * @return void
     * 
     * @see https://developer.wordpress.org/reference/functions/wp_insert_user/
     */
    function update_user(array $userarr, bool $require_permission = true)
    {
        if ($require_permission) {
            if (!$this->has_cap("edit_users") && get_current_user_id() != $this->ID) {
                // Either can edit all users or is this user.
                new ReportableError(static::ERROR_SCOPE, __("You don't have permission to perform this action.", "wrd"));
                return false;
            }
        }

        $requires_token = ["user_pass", "user_email"];

        foreach ($requires_token as $token_field) {
            if (!array_key_exists($token_field, $userarr)) {
                continue;
            }

            if (!$this->check_token($token_field, $userarr[$token_field])) {
                new ReportableError(static::ERROR_SCOPE, __("Your token has expired.", "wrd"));
                unset($userarr[$token_field]);
            }
        }

        if (empty($userarr)) {
            return false;
        }

        $userarr = static::validate_user_data($userarr);

        if (!$userarr) {
            return false;
        }

        $userarr["ID"] = $this->ID;

        $user_id = wp_update_user($userarr);

        if (is_wp_error($user_id)) {
            new ReportableError(static::ERROR_SCOPE, $user_id->get_error_message());
            return false;
        }

        return true;
    }

    /**
     * Generates a secure token for a field and stores in the user meta.
     * 
     * @param string $expires The datetime when the token will no longer be valid. Must be parseable by strtotime();
     * 
     * @return string $token The token the user must provide.
     * 
     * @see https://www.php.net/manual/en/function.strtotime.php
     */
    function generate_token(string $field, string $expires = null)
    {
        $defaultExpiresDuration = 60 * 60 * 2; // 2 hours

        $token = bin2hex(openssl_random_pseudo_bytes(16));

        if (!$expires) {
            $expires = time() + $defaultExpiresDuration;
        } else {
            $expires = strtotime($expires);
        }

        update_user_meta($this->ID, "{$field}_token", $token);
        update_user_meta($this->ID, "{$field}_token_expires", $expires);

        return $token;
    }

    /**
     * Checks if a given value for a fields token is correct and not expired.
     * 
     * @param string $field The field the token is for.
     * @param string $given The token value given from the client.
     * 
     * @return bool $valid If the token is correct and in date.
     */
    function check_token(string $field, string $given)
    {
        $token = get_user_meta($this->ID, "{$field}_token", true);
        $expires = get_user_meta($this->ID, "{$field}_token_expires", true);

        if ($token !== $given) {
            new ReportableError(static::ERROR_SCOPE, __("Token's do not match. ($token != $given)"));
            return false;
        }

        // If token expired
        $current_time = strtotime("now");

        if ($current_time > $expires) {
            new ReportableError(static::ERROR_SCOPE, __("Token expired."));
            return false;
        }

        return true;
    }

    /**
     * Resets the email token and emails the user the link.
     * 
     * @return void
     */
    function send_verify_email()
    {
        $token = $this->generate_token("user_email");
        $url = home_url(WRD_MEMBERS_VERIFY_SLUG);

        $url = add_query_arg([
            "login" => $this->user->user_login,
            "token" => $token
        ], $url);

        new Email([
            "to"       => $this->user->user_email,
            "subject"  => sprintf(__("Confirm your Email Address - %s", "wrd"), get_bloginfo("name")),
            "message"  => [
                "title" => sprintf(__("%s, Please Confirm your Email Address", "wrd"), $this->user->display_name),
                "body" => __("", "wrd"),
                "cta" => "Verify Now",
                "link" => $url,
            ]
        ]);
    }

    /**
     * Attempts to verify the user's email address.
     */
    function verify(string $token)
    {
        if ($this->check_token("user_email", $token)) {
            update_user_meta($this->ID, static::VERIFIED_META_KEY, true);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Resets the password token and emails the user the link.
     * 
     * @param string $url URL to send the user to so they can verify.
     * @param strign $email_template Path to the HTML template for the email
     * 
     * @return void
     */
    function send_reset_password_email()
    {
        $token = $this->generate_token("user_pass");
        $url = home_url(WRD_MEMBERS_UPDATE_PASSWORD_SLUG);

        $url = add_query_arg([
            "login" => $this->user->user_login,
            "token" => $token
        ], $url);

        new Email([
            "to"       => $this->user->user_email,
            "subject"  => sprintf(__("Reset your Password - %s", "wrd"), get_bloginfo("name")),
            "message"  => [
                "title" => sprintf(__("%s, Click the link below to reset your password.", "wrd"), $this->user->display_name),
                "body" => __("", "wrd"),
                "cta" => "Update Password",
                "link" => $url,
            ]
        ]);
    }

    function update_password($userarr)
    {
        $params = ["user_pass", "confirm_pass", "token"];

        // Strip unallowed fields
        foreach ($userarr as $field => $value) {
            if (!in_array($field, $params)) {
                unset($userarr[$field]);
            }
        }

        foreach ($params as $param) {
            if (!array_key_exists($param, $userarr)) {
                new ReportableError(static::ERROR_SCOPE, __("Please fill in all required fields.", "wrd"));
                return false;
            }
        }

        // Check token
        if (!$this->check_token("user_pass", $userarr["token"])) {
            new ReportableError(static::ERROR_SCOPE, __("Your token has expired.", "wrd"));
            return false;
        }

        // Validate password
        $userarr = static::validate_user_data($userarr);

        if (!$userarr) {
            return false;
        }

        // Update user
        $userarr["ID"] = $this->ID;
        $user_id = wp_update_user($userarr);

        // Error catch
        if (is_wp_error($user_id)) {
            new ReportableError(static::ERROR_SCOPE, $user_id->get_error_message());
            return false;
        }

        return true;
    }

    /**
     * Checks if the user has confirmed ownership of their email address.
     * 
     * @return bool $verified
     */
    function is_verified()
    {
        $meta_value = get_user_meta($this->ID, static::VERIFIED_META_KEY, true);
        return boolval($meta_value); // True is stored as 1, false as empty string.
    }

    /**
     * Checks if the user has permission to do an action.
     * 
     * @param $capability Name of the capability.
     * @param $args Arguements to pass. Normally the post ID.
     * 
     * @return bool $granted If the user has permission.
     */
    function has_cap(string $capability, ...$args)
    {
        if (!$this->is_verified()) {
            new ReportableError(static::ERROR_SCOPE, __("Please verify your account before doing this.", 'wrd'));
            return false; // Unverified users cannot do anything.
        }

        $allowed = $this->user->has_cap($capability, ...$args);

        var_dump($args);

        $allowed = apply_filters("user_can_$capability", $allowed, $this, ...$args);

        return $allowed;
    }

    /**
     * Grant the user a capability.
     * 
     * @param string $capability The capability to give.
     */
    function add_cap(string $capability)
    {
        return $this->user->add_cap($capability);
    }

    /**
     * Remove a users capability.
     * 
     * @param string $capability The capability to remove.
     */
    function remove_cap(string $capability)
    {
        return $this->user->remove_cap($capability);
    }

    /**
     * Set the role of the user.
     * 
     * Removes the previous roles and assign the user the new one. Give an empty string to remove all of the roles.
     * 
     * @param string $role Role name.
     */
    function set_role(string $role)
    {
        return $this->user->set_role($role);
    }

    /**
     * Adds a new capability to any number of roles with a callback function to decide if the user can perform the action.
     * 
     * The callback function should return true if the user has the capability.
     * Recieves a CustomUser object as the first argument. Subsequent arguements depend on the has_cap call but are usually the post ID.
     * 
     * @param string $capability The capability to add.
     * @param callable $callback The function to determine if a user passes the permission check.
     * @param array $roles Optional. List of roles to add the capability to.
     */
    static function create_cap(string $capability, $callback, array $roles = [], int $accepted_args = 1)
    {
        /**
         * callback(CustomUser $user, ...$args)
         */
        add_filter("user_can_$capability", $callback, 10, $accepted_args);

        foreach ($roles as $role) {
            $role = get_role($role);

            if ($role) {
                $role->add_cap($capability, true);
            }
        }
    }

    /**
     * Sends a message from a user to this one.
     */
    function send_message_to(CustomUser $from, string $message)
    {
        UserMessage::send_message($this, $message, $from);
    }

    /**
     * Sends a message from this user to another.
     */
    function send_message_from(CustomUser $to, string $message)
    {
        UserMessage::send_message($to, $message, $this);
    }

    /**
     * Returns array of all messages between this user and another.
     */
    function get_conversation_with(CustomUser $user)
    {
        return UserMessage::get_conversation($this, $user);
    }

    /**
     * Sets all messages in a conversation with a user as read.
     * 
     * Only marks messages not sent by this user.
     */
    function mark_conversion_read(CustomUser $user)
    {
        $messages = $this->get_conversation_with($user);

        foreach ($messages as $message) {
            $from = $message->get_from_user();

            if ($from->ID != $this->ID) {
                $message->mark_as_read();
            }
        }
    }

    /**
     * Returns all notifications in date order for a user.
     */
    function get_notifications()
    {
        return UserNotification::get_by_user($this);
    }

    /**
     * Returns all unread notifications in date order for a user.
     */
    function get_unread_notifications()
    {
        return UserNotification::get_unread_by_user($this);
    }

    /**
     * Returns all read notifications in date order for a user.
     */
    function get_read_notifications()
    {
        return UserNotification::get_read_by_user($this);
    }

    /**
     * Sets the read status on all notifications to read.
     */
    function mark_all_notifications_read()
    {
        $notifications = UserNotification::get_by_user($this);

        foreach ($notifications as $not) {
            $not->mark_as_read();
        }
    }
}
