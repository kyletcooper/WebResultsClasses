<?php

namespace wrd;

class UserNotification extends UserMessage
{
    const NOTIFICATION_LABEL = "Notification";
    const NOTIFICATION_ID = -1;

    /**
     * Sends a notification to a user.
     * 
     * @param CustomUser $to The user recieving the notification.
     * @param string $message The text content of the notification.
     * @param CustomUser $from This parameter is ignored.
     */
    static function send_message(CustomUser $to, string $message, CustomUser $from = null)
    {
        $to = intval($to->ID);
        $from = static::NOTIFICATION_ID;
        $message = sanitize_text_field(stripslashes($message));

        static::create_message($to, $from, $message);
    }

    /**
     * Alias of send_message()
     * 
     * @see send_message()
     */
    static function send_notification(CustomUser $to, string $message)
    {
        return static::send_message($to, $message);
    }

    /**
     * Returns an users notifications.
     * 
     * @param int $user The user to get notifications for.
     * 
     * @return array $notifications Array of UserNotifications.
     */
    static function get_by_user(CustomUser $user)
    {
        $ID = $user->ID;
        $table = CustomTable::get_instance(static::TABLE_NAME);

        $notifications = $table->select([
            "to_user" => $ID,
        ]);

        return static::array_to_object($notifications);
    }

    /**
     * Returns an users unread notifications.
     * 
     * @param int $user The user to get notifications for.
     * 
     * @return array $notifications Array of UserNotifications.
     */
    static function get_unread_by_user(CustomUser $user)
    {
        $ID = $user->ID;
        $table = CustomTable::get_instance(static::TABLE_NAME);

        $notifications = $table->select([
            "to_user" => $ID,
            "read" => 0
        ]);

        return static::array_to_object($notifications);
    }

    /**
     * Returns an users read notifications.
     * 
     * @param int $user The user to get notifications for.
     * 
     * @return array $notifications Array of UserNotifications.
     */
    static function get_read_by_user(CustomUser $user)
    {
        $ID = $user->ID;
        $table = CustomTable::get_instance(static::TABLE_NAME);

        $notifications = $table->select([
            "to_user" => $ID,
            "read" => 1
        ]);

        return static::array_to_object($notifications);
    }

    /**
     * Returns the user who sent the message.
     * 
     * Overrides get_from_user in UserMessage.
     * 
     * @return string $notification_label 
     */
    function get_from_user()
    {
        return static::NOTIFICATION_LABEL;
    }
}
