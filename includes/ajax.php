<?php
/**
 * Notes Plugin - AJAX handlers (create, edit)
 */

// No direct call
if (!defined('YOURLS_ABSPATH'))
    die();

// ─── AJAX handler: create a new note ───
yourls_add_action('yourls_ajax_notes_add', 'notes_ajax_add');
function notes_ajax_add()
{
    yourls_verify_nonce('notes_add', $_REQUEST['nonce'], false, 'omg error');

    $note_content = isset($_REQUEST['note_content']) ? trim($_REQUEST['note_content']) : '';
    $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';

    if (empty($note_content)) {
        echo json_encode(array(
            'status' => 'fail',
            'message' => yourls__('Note content cannot be empty'),
        ));
        return;
    }

    // Use a valid HTTPS URL as placeholder. Each note gets a unique URL to avoid duplicate checks.
    $internal_url = NOTES_INTERNAL_PREFIX . uniqid('n');

    // Use the standard YOURLS function to create the short URL entry
    $result = yourls_add_new_link($internal_url, $keyword, 'Note');

    if ($result['status'] === 'success') {
        $saved_keyword = $result['url']['keyword'];

        // Save the note content in our custom table
        $table = notes_get_table();
        $ydb = yourls_get_db();

        $render_md = isset($_REQUEST['render_md']) ? intval($_REQUEST['render_md']) : 0;

        $ydb->fetchAffected(
            "INSERT INTO `$table` (`keyword`, `note_content`, `render_md`, `created_at`) VALUES (:keyword, :content, :render_md, :now)",
            array(
                'keyword' => $saved_keyword,
                'content' => $note_content,
                'render_md' => $render_md,
                'now' => date('Y-m-d H:i:s'),
            )
        );

        // Regenerate the table row HTML now that the note exists in DB
        // (the original HTML was generated before the note was saved, so the badge filter didn't apply)
        $row_id = isset($_REQUEST['rowid']) ? intval($_REQUEST['rowid']) : 1;
        $result['html'] = yourls_table_add_row(
            $saved_keyword,
            $result['url']['url'],
            $result['url']['title'],
            $result['url']['ip'],
            0,
            $result['url']['date'],
            $row_id
        );

        $result['message'] = yourls__('Note created successfully');
    }

    echo json_encode($result);
}

// ─── AJAX handler: save edited note ───
yourls_add_action('yourls_ajax_notes_edit_save', 'notes_ajax_edit_save');
function notes_ajax_edit_save()
{
    yourls_verify_nonce('edit-save_' . $_REQUEST['id'], $_REQUEST['nonce'], false, 'omg error');

    $keyword = isset($_REQUEST['keyword']) ? yourls_sanitize_keyword($_REQUEST['keyword']) : '';
    $newkeyword = isset($_REQUEST['newkeyword']) ? yourls_sanitize_keyword($_REQUEST['newkeyword'], true) : $keyword;
    $note_content = isset($_REQUEST['note_content']) ? trim($_REQUEST['note_content']) : '';
    $render_md = isset($_REQUEST['render_md']) ? intval($_REQUEST['render_md']) : 0;

    if (empty($note_content)) {
        echo json_encode(array('status' => 'fail', 'message' => yourls__('Note content cannot be empty')));
        return;
    }

    $table = notes_get_table();
    $ydb = yourls_get_db();

    // Handle keyword change if needed
    if ($newkeyword && $newkeyword !== $keyword) {
        if (!yourls_keyword_is_free($newkeyword)) {
            echo json_encode(array('status' => 'fail', 'message' => yourls__('Short URL already exists')));
            return;
        }
        // Update keyword in YOURLS url table
        $url_table = YOURLS_DB_TABLE_URL;
        $ydb->fetchAffected(
            "UPDATE `$url_table` SET `keyword` = :newkeyword WHERE `keyword` = :keyword",
            array('newkeyword' => $newkeyword, 'keyword' => $keyword)
        );
        // Update keyword in notes table
        $ydb->fetchAffected(
            "UPDATE `$table` SET `keyword` = :newkeyword WHERE `keyword` = :keyword",
            array('newkeyword' => $newkeyword, 'keyword' => $keyword)
        );
        $keyword = $newkeyword;
    }

    // Update note content and render_md
    $ydb->fetchAffected(
        "UPDATE `$table` SET `note_content` = :content, `render_md` = :render_md WHERE `keyword` = :keyword",
        array('content' => $note_content, 'render_md' => $render_md, 'keyword' => $keyword)
    );

    $preview = mb_strimwidth(str_replace("\n", ' ', $note_content), 0, 60, '...');
    $return = array(
        'status' => 'success',
        'message' => yourls__('Note updated successfully'),
        'note_preview' => $preview,
        'url' => array(
            'keyword' => $keyword,
            'shorturl' => yourls_link($keyword),
            'url' => NOTES_INTERNAL_PREFIX . 'note',
            'display_url' => 'Note',
            'title' => 'Note',
            'display_title' => 'Note',
        ),
    );
    echo json_encode($return);
}
