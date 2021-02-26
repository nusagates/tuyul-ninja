<?php

namespace App\Includes;

class TuyulInit
{
    private $DB;

    public function __construct()
    {
        global $wpdb;
        $this->DB = $wpdb;
        register_activation_hook(TUYUL_PLUGIN_FILE_URL, [$this, 'activate']);
        register_deactivation_hook(TUYUL_PLUGIN_FILE_URL, [$this, 'deactivate']);
    }

    public function activate()
    {

        add_option('wpty_db_version', TUYUL_DB_VERSION);
        $charset_collate = $this->DB->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        if ($this->DB->get_var("SHOW TABLES LIKE '{" . TUYUL_TABLE_JOBS . "}'") != TUYUL_TABLE_JOBS) {

            $sql = "CREATE TABLE " . TUYUL_TABLE_JOBS . " (
                        id mediumint(9) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                        `name` varchar(100) NOT NULL DEFAULT '',
                        `provider` varchar(50) NOT NULL DEFAULT 'email',
                        `meta` longtext NOT NULL,
                        `created_at` DATETIME NOT NULL DEFAULT current_timestamp()
                        ) $charset_collate;";

            dbDelta($sql);

        }
        if ($this->DB->get_var("SHOW TABLES LIKE '{" . TUYUL_TABLE_HISTORIES . "}'") != TUYUL_TABLE_HISTORIES) {

            $sql = "CREATE TABLE " . TUYUL_TABLE_HISTORIES . " (
                        id mediumint(9) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                       `post_id` int(11) NOT NULL,
                        `job_id` int(11) NOT NULL,
                        `created_at` DATETIME NOT NULL DEFAULT current_timestamp()
                        ) $charset_collate;";
            dbDelta($sql);


        }

    }

    public static function deactivate()
    {
        delete_option('wpty_priority');
        delete_option('wpty_email');
        delete_option('wpty_period');
        delete_option('wpty_db_version');
        wp_clear_scheduled_hook('send_post_to_blogger');
        //global $wpdb;
        //$wpdb->query("DROP TABLE IF EXISTS " . TUYUL_TABLE_HISTORIES);
        //$wpdb->query("DROP TABLE IF EXISTS " . TUYUL_TABLE_JOBS);
    }
}