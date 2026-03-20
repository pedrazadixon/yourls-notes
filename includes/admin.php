<?php
/**
 * Notes Plugin - Admin UI (form, table rows, edit row, search, assets)
 */

// No direct call
if (!defined('YOURLS_ABSPATH'))
    die();

// ─── Enqueue plugin CSS & JS in admin ───
yourls_add_action('html_head', 'notes_enqueue_assets');
function notes_enqueue_assets($context)
{
    if (!yourls_is_admin())
        return;

    $plugin_url = yourls_plugin_url(NOTES_PLUGIN_DIR);
    echo '<link rel="stylesheet" href="' . $plugin_url . '/assets/notes.css?v=1.1" type="text/css" />' . "\n";
    echo '<script src="' . $plugin_url . '/assets/notes.js?v=1.1" type="text/javascript"></script>' . "\n";
}

// ─── Add "Create Note" form after the URL form ───
yourls_add_action('html_addnew', 'notes_add_form');
function notes_add_form()
{
    $nonce = yourls_create_nonce('notes_add');
    ?>
    <div id="notes-toggle-wrap">
        <button type="button" id="notes-toggle-btn" class="button" onclick="notes_toggle_form()">
            📝 <?php yourls_e('Create a Note'); ?>
        </button>
    </div>
    <div id="notes-form-wrap" style="display:none;">
        <form id="notes-form" action="" method="post">
            <div class="notes-form-inner">
                <label for="note-content"><strong><?php yourls_e('Note Content'); ?></strong></label>
                <textarea id="note-content" name="note_content" rows="6"
                    placeholder="<?php yourls_e('Write your note here...'); ?>"></textarea>
                <div class="notes-form-row">
                    <label for="note-keyword"><?php yourls_e('Optional'); ?> :
                        <strong><?php yourls_e('Custom short URL'); ?></strong></label>
                    <input type="text" id="note-keyword" name="keyword" class="text" size="8" />
                    <label class="notes-checkbox-label">
                        <input type="checkbox" id="note-render-md" name="render_md" value="1" checked />
                        <?php yourls_e('Render Markdown'); ?>
                    </label>
                    <input type="hidden" id="note-nonce" value="<?php echo $nonce; ?>" />
                    <input type="button" id="note-submit" value="<?php yourls_e('Create Note'); ?>" class="button"
                        onclick="notes_add();" />
                </div>
            </div>
        </form>
        <div id="note-feedback" style="display:none;"></div>
    </div>
    <?php
}

// ─── Admin table: mark notes with a badge ───
yourls_add_filter('table_add_row_cell_array', 'notes_modify_table_row');
function notes_modify_table_row($cells, $keyword)
{
    if (notes_is_note($keyword)) {
        $content = notes_get_content($keyword);
        $preview = mb_strimwidth(str_replace("\n", ' ', $content), 0, 60, '...');
        $cells['url'] = array(
            'template' => '
                <a href="%shorturl%" title="%shorturl%">Note %keyword%</a><br/>
                <small class="notes-badge-note">%note_preview%</small>',
            'shorturl' => yourls_esc_html(yourls_link($keyword)),
            'note_preview' => yourls_esc_html($preview),
            'keyword' => yourls_esc_html($keyword),
        );
    }
    return $cells;
}

// ─── Custom edit row for notes: show textarea instead of URL field ───
yourls_add_filter('table_edit_row', 'notes_custom_edit_row');
function notes_custom_edit_row($return, $keyword, $url, $title)
{
    if (!notes_is_note($keyword)) {
        return $return;
    }

    $note_content = notes_get_content($keyword);
    $note = notes_get_note($keyword);
    $safe_content = yourls_esc_textarea($note_content);
    $safe_keyword = yourls_esc_attr($keyword);
    $render_md = $note ? (int) $note->render_md : 1;
    $checked = $render_md ? 'checked' : '';

    preg_match('/id="edit-([^"]+)"/', $return, $m);
    $id = isset($m[1]) ? $m[1] : '';

    if (!$id)
        return $return;

    $www = yourls_link();
    $nonce = yourls_create_nonce('edit-save_' . $id);

    $return = <<<ROW
        <tr id="edit-$id" class="edit-row">
            <td colspan="5" class="edit-row">
                <strong>Note Content</strong>:<br/>
                <textarea id="edit-note-content-$id" rows="5" style="width:96%;font-family:inherit;font-size:14px;padding:6px 10px;border:1px solid #ccc;border-radius:4px;resize:vertical;">$safe_content</textarea>
                <br/>
                <strong>Short URL</strong>: $www<input type="text" id="edit-keyword-$id" name="edit-keyword-$id" value="$safe_keyword" class="text" size="10" />
                &nbsp;
                <label style="font-weight:normal;"><input type="checkbox" id="edit-render-md-$id" $checked /> Render Markdown</label>
            </td>
            <td colspan="1">
                <input type="button" id="edit-submit-$id" name="edit-submit-$id" value="Save" title="Save" class="button" onclick="notes_edit_save('$id');" />
                &nbsp;
                <input type="button" id="edit-close-$id" name="edit-close-$id" value="Cancel" title="Cancel" class="button" onclick="edit_link_hide('$id');" />
                <input type="hidden" id="old_keyword_$id" value="$safe_keyword"/>
                <input type="hidden" id="nonce_$id" value="$nonce"/>
            </td>
        </tr>
    ROW;

    return $return;
}

// ─── Search filter: include note_content in search ───
yourls_add_filter('admin_list_where', 'notes_search_filter');
function notes_search_filter($where)
{
    if (empty($where['sql']) || !isset($where['binds']['search'])) {
        return $where;
    }

    $notes_table = notes_get_table();
    $url_table = YOURLS_DB_TABLE_URL;

    $where['sql'] = str_replace(
        "AND `keyword` LIKE (:search)\n                        OR `url` LIKE (:search)",
        "AND (`keyword` LIKE (:search)\n                        OR `url` LIKE (:search)",
        $where['sql']
    );

    if (strpos($where['sql'], 'OR `url` LIKE') !== false || strpos($where['sql'], 'AND `url`') !== false) {
        $where['sql'] .= " OR `$url_table`.`keyword` IN (SELECT `keyword` FROM `$notes_table` WHERE `note_content` LIKE (:search))";
    }

    if (strpos($where['sql'], 'AND (`keyword`') !== false) {
        $where['sql'] .= ")";
    }

    return $where;
}
