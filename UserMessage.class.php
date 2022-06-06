<?php

namespace wrd;

/**
 * Manages private messages between users in a custom database table.
 */

class UserMessage
{
    const CAPABILITY = "send_message";
    const TABLE_NAME = "wrd_user_messages";

    function __construct(int $notification_id)
    {
        $this->ID = intval($notification_id);
        $this->table = CustomTable::get_instance(static::TABLE_NAME);
        $this->row = $this->table->get_id($this->ID);

        if ($this->row === null) {
            throw new \Exception("Message not found.");
        }

        $this->is_read      = boolval($this->row["is_read"]);
        $this->message      = $this->row["message"];
        $this->timestamp    = $this->row["timestamp"];
        $this->from         = $this->row["from_user"];
        $this->to           = $this->row["to_user"];
    }

    /**
     * Creates a message in the database.
     * 
     * @param int $to The value of the to_user field. Should be the ID of a user in most cases.
     * @param int $from The value of the from_user field. Should be the ID of a user in most cases.
     * @param string $message The text content of the message.
     * 
     * @return UserMessage|false The new message object or false on error.
     */
    private static function create_message($to, $from, $message)
    {
        $table = CustomTable::get_instance(static::TABLE_NAME);

        $ID = $table->insert([
            "to_user" => $to,
            "from_user" => $from,
            "message" => $message,
        ]);

        if (!$ID) {
            return false;
        }

        return new static($ID);
    }

    /**
     * Sends a message from one user to another.
     * 
     * @param CustomUser $to User recieving the message.
     * @param string $message The text content of the message.
     * @param CustomUser $from User sending the message.
     * 
     * @return UserMessage|false The new message object or false on error.
     */
    static function send_message(CustomUser $to, string $message, CustomUser $from = null)
    {
        if ($from === null) {
            $from = CustomUser::current();
        }

        if (!$from->has_cap(static::CAPABILITY, $to->ID)) {
            return false; // User is banned from messaging
        }

        $to = intval($to->ID);
        $from = intval($from->ID);
        $message = sanitize_text_field(stripslashes($message));

        static::create_message($to, $from, $message);
    }

    /**
     * Returns an array of all messages between two users.
     * 
     * The order of the two users does not matter.
     * 
     * @param CustomUser $a User 1.
     * @param CustomUser $b User 2.
     * @param string $sort_dir Order to sort timestamps. SORT_DESC or SORT_ASC
     * 
     * @return array $messages Array of messages, or empty array on error.
     */
    static function get_conversation(CustomUser $a, CustomUser $b, $sort_dir = SORT_DESC)
    {
        $table = CustomTable::get_instance(static::TABLE_NAME);

        $messages_a_to_b = $table->select([
            "from_user" => $a->ID,
            "to_user" => $b->ID,
        ]);

        $messages_b_to_a = $table->select([
            "from_user" => $b->ID,
            "to_user" => $a->ID,
        ]);

        $messages = [...$messages_a_to_b, ...$messages_b_to_a];

        $col = array_column($messages, "timestamp");
        array_multisort($col, $sort_dir, $messages);

        return static::array_to_object($messages);
    }

    /**
     * Converts an array of database rows to an array of objects.
     * 
     * @param array $arr Array of database rows.
     * 
     * @return array $out Array of static objects (e.i. UserMessage or UserNotification).
     */
    static function array_to_object(array $arr)
    {
        $out = [];

        foreach ($arr as $message) {
            $out[] = new static($message['id']);
        }

        return $out;
    }

    /**
     * @return bool Success of the update.
     */
    function mark_as_read()
    {
        return $this->table->update_id(
            ['is_read' => '1'],
            $this->ID
        );
    }

    /**
     * Returns true if the message has been viewed before.
     * 
     * @return bool $is_read
     */
    function is_read()
    {
        return $this->is_read;
    }

    /**
     * Returns the user that recieved the message.
     * 
     * @return CustomUser $to
     */
    function get_to_user()
    {
        return new CustomUser($this->to);
    }

    /**
     * Returns the user that sent the message.
     * 
     * @return CustomUser $from
     */
    function get_from_user()
    {
        return new CustomUser($this->from);
    }

    /**
     * Returns the content of the message.
     * 
     * @return string $message
     */
    function get_message()
    {
        return $this->message;
    }

    /**
     * Returns the timestamp when the message was sent.
     * 
     * @return string $timestamp
     */
    function get_timestamp()
    {
        return $this->timestamp;
    }
}

add_action("direct_ready", function () {
    new CustomTable(UserMessage::TABLE_NAME, "1.0.0", [
        "timestamp" => "datetime DEFAULT CURRENT_TIMESTAMP NOT NULL",
        "to_user"   => "smallint(5) NOT NULL",
        "from_user" => "smallint(5) NOT NULL",
        "message"   => "text NOT NULL",
        "is_read"   => "bit DEFAULT 0 NOT NULL"
    ]);
});
