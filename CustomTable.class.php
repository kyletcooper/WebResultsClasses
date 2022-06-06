<?php

namespace wrd;

class CustomTable
{
    private static $instances = [];

    /**
     * Creates a custom table in the database and handles version controlling it.
     * 
     * If you're looking to get an existing table, use get_instance().
     * 
     * Fields is an array where the key is the name of the column and the value is any data needed to create the row. IDs are added automatically.
     * E.g. ["timestamp" => "datetime DEFAULT CURRENT_TIMESTAMP NOT NULL"]
     */
    function __construct(string $name, string $version, array $fields)
    {
        $this->name = $name;
        $this->version = $version;
        $this->fields = $fields;

        $this->create_table();

        static::$instances[$name] = $this;
    }

    /**
     * Returns a table by name.
     */
    static function get_instance(string $name)
    {
        if (!array_key_exists($name, static::$instances)) {
            throw new \Exception("CustomTables must be constructed before their instance can be returned.");
        }

        return static::$instances[$name];
    }

    /**
     * Returns the prefixed name of the table.
     */
    function get_table_name()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->table_name;

        return $table_name;
    }

    /**
     * Gets the version currently in use.
     */
    function get_version_option()
    {
        return get_site_option($this->name . "_version");
    }

    /**
     * Sets the version option to the current version.
     */
    function set_version_option()
    {
        return update_site_option($this->name . "_version", $this->version);
    }

    /**
     * Creates the table. Does not create the table if the installed verion is the current version.
     */
    function create_table()
    {
        if ($this->get_version_option() == $this->version) {
            return;
        }

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $this->get_table_name();

        $fields = $this->fields_to_sql();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            $fields
            PRIMARY KEY  (id)
	    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $this->set_version_option();
    }

    /**
     * Converts an array of fields to SQL.
     */
    function fields_to_sql()
    {
        $sql = "";

        foreach ($this->fields as $field => $details) {
            $sql .= "$field $details,\n";
        }
    }

    /**
     * @return int|false $insert_id The ID of the new row or false on failure.
     */
    function insert(array $data)
    {
        global $wpdb;
        $table_name = $this->get_table_name();

        $success = $wpdb->insert($table_name, $data);

        if (!$success) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Updates a row.
     */
    function update(array $data, array $where)
    {
        global $wpdb;
        $table_name = $this->get_table_name();

        $success = $wpdb->update(
            $table_name,
            $data,
            $where,
        );

        return boolval($success);
    }

    /**
     * Updates a row by ID.
     */
    function update_id(array $data, int $ID)
    {
        return $this->update($data, ["id" => $ID]);
    }

    /**
     * Gets a row by ID.
     */
    function get_by_id($ID)
    {
        global $wpdb;
        $table_name = $this->get_table_name();

        $ID = intval($ID);

        return $wpdb->get_row("SELECT * FROM $table_name WHERE id = $ID", ARRAY_A);
    }

    /**
     * Gets a row by an array of fields.
     */
    function select(array $fields)
    {
        global $wpdb;
        $table_name = $this->get_table_name();

        $query = "SELECT * FROM $table_name WHERE 1=1 ";
        $values = [];

        foreach ($fields as $field => $value) {
            $query .= "AND $field = %s ";
            $values[] = $value;
        }

        $sql = $wpdb->prepare($query, ...$values);

        return $wpdb->query($sql);
    }
}
