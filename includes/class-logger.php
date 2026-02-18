<?php
if (!defined('ABSPATH')) exit;

class PST_Logger {

    public static function create_table(){

        global $wpdb;

        $table = PST_LOG_TABLE;

        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            time DATETIME,
            role VARCHAR(20),
            action VARCHAR(50),
            host_post_id BIGINT,
            target_post_id BIGINT,
            target_url TEXT,
            status VARCHAR(20),
            message TEXT,
            duration FLOAT
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function log($data){

        global $wpdb;

        $wpdb->insert(PST_LOG_TABLE, [
            'time' => current_time('mysql'),
            'role' => sanitize_text_field($data['role']),
            'action' => sanitize_text_field($data['action']),
            'host_post_id' => intval($data['host_post_id']),
            'target_post_id' => intval($data['target_post_id']),
            'target_url' => esc_url_raw($data['target_url']),
            'status' => sanitize_text_field($data['status']),
            'message' => sanitize_textarea_field($data['message']),
            'duration' => floatval($data['duration'])
        ]);
    }

}
