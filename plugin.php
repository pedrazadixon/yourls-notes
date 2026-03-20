<?php
/*
Plugin Name: Notes
Plugin URI: https://github.com/pedrazadixon/yourls-notes
Description: Create short URLs that display text or markdown notes instead of redirecting to a URL
Version: 1.0
Author: pedrazadixon
Author URI: https://github.com/pedrazadixon
*/

// No direct call
if (!defined('YOURLS_ABSPATH'))
    die();

// ─── Constants ───
define('NOTES_PLUGIN_DIR', dirname(__FILE__));
define('NOTES_INTERNAL_PREFIX', 'note://');

// ─── Register custom protocol so YOURLS accepts note:// URLs ───
yourls_add_filter('kses_allowed_protocols', 'notes_register_protocol');
function notes_register_protocol($protocols) {
    $protocols[] = 'note://';
    return $protocols;
}

// ─── Load plugin modules ───
require_once NOTES_PLUGIN_DIR . '/includes/helpers.php';
require_once NOTES_PLUGIN_DIR . '/includes/admin.php';
require_once NOTES_PLUGIN_DIR . '/includes/ajax.php';
require_once NOTES_PLUGIN_DIR . '/includes/display.php';
require_once NOTES_PLUGIN_DIR . '/includes/stats.php';
require_once NOTES_PLUGIN_DIR . '/includes/settings.php';

// ─── Activation: create the notes table ───
yourls_add_action('activated_yourls-notes/plugin.php', 'notes_plugin_activate');
function notes_plugin_activate()
{
    global $ydb;

    $table = YOURLS_DB_TABLE_URL;
    // Derive prefix from the URL table name (e.g. "yourls_" from "yourls_url")
    $prefix = substr($table, 0, strrpos($table, '_') + 1);
    $notes_table = $prefix . 'notes';

    $sql = "CREATE TABLE IF NOT EXISTS `$notes_table` (
        `keyword`      VARCHAR(200) NOT NULL,
        `note_content` TEXT         NOT NULL,
        `render_md`    TINYINT(1)   NOT NULL DEFAULT 1,
        `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`keyword`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $ydb = yourls_get_db();
    $ydb->fetchAffected($sql);

    // Migration: add render_md column if it doesn't exist (for tables created before v1.1)
    try {
        $ydb->fetchAffected(
            "ALTER TABLE `$notes_table` ADD COLUMN `render_md` TINYINT(1) NOT NULL DEFAULT 1 AFTER `note_content`"
        );
    } catch (Exception $e) {
        // Column already exists — ignore
    }

    // Store the table name as an option for easy retrieval
    yourls_update_option('notes_table', $notes_table);
}

// ─── Cleanup: delete note when short URL is deleted ───
yourls_add_action('delete_link', 'notes_delete_note');
function notes_delete_note($args)
{
    $keyword = $args[0];
    $table = notes_get_table();
    yourls_get_db()->fetchAffected(
        "DELETE FROM `$table` WHERE `keyword` = :keyword",
        array('keyword' => $keyword)
    );
}
