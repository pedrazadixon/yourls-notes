<?php
/**
 * Notes Plugin - Helper functions
 */

// No direct call
if (!defined('YOURLS_ABSPATH'))
    die();

// ─── Helper: get notes table name ───
function notes_get_table()
{
    $table = yourls_get_option('notes_table');
    if (!$table) {
        // Fallback: derive from URL table
        $url_table = YOURLS_DB_TABLE_URL;
        $prefix = substr($url_table, 0, strrpos($url_table, '_') + 1);
        $table = $prefix . 'notes';
    }
    return $table;
}

// ─── Helper: check if a keyword is a note ───
function notes_is_note($keyword)
{
    $table = notes_get_table();
    $result = yourls_get_db()->fetchValue(
        "SELECT COUNT(*) FROM `$table` WHERE `keyword` = :keyword",
        array('keyword' => $keyword)
    );
    return intval($result) > 0;
}

// ─── Helper: get note content ───
function notes_get_content($keyword)
{
    $table = notes_get_table();
    return yourls_get_db()->fetchValue(
        "SELECT `note_content` FROM `$table` WHERE `keyword` = :keyword",
        array('keyword' => $keyword)
    );
}

// ─── Helper: get note data (content + render_md) ───
function notes_get_note($keyword)
{
    $table = notes_get_table();
    return yourls_get_db()->fetchObject(
        "SELECT `note_content`, `render_md` FROM `$table` WHERE `keyword` = :keyword",
        array('keyword' => $keyword)
    );
}
