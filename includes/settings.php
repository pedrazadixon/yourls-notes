<?php
/**
 * Notes Plugin - Settings page (truncate notes table)
 */

// No direct call
if (!defined('YOURLS_ABSPATH'))
    die();

// ─── Register the settings page ───
yourls_add_action('plugins_loaded', 'notes_register_settings');
function notes_register_settings()
{
    yourls_register_plugin_page('notes_settings', 'Notes Settings', 'notes_settings_page');
}

// ─── Render the settings page ───
function notes_settings_page()
{
    $table = notes_get_table();

    // Handle form submission
    if (isset($_POST['notes_action'])) {
        yourls_verify_nonce('notes_settings');

        if ($_POST['notes_action'] === 'truncate') {
            $ydb = yourls_get_db();
            $ydb->fetchAffected("TRUNCATE TABLE `$table`");

            // Also delete the corresponding entries from the URL table
            $url_table = YOURLS_DB_TABLE_URL;
            $ydb->fetchAffected(
                "DELETE FROM `$url_table` WHERE `url` LIKE :prefix",
                array('prefix' => NOTES_INTERNAL_PREFIX . '%')
            );

            echo '<p style="color:green;"><strong>✅ Notes table truncated and related URLs removed.</strong></p>';
        }
    }

    // Count notes
    $ydb = yourls_get_db();
    $count = $ydb->fetchValue("SELECT COUNT(*) FROM `$table`");
    $nonce = yourls_create_nonce('notes_settings');

    echo <<<HTML
        <main>
            <h2>Notes Settings</h2>

            <h3>Notes Table</h3>
            <p>Table name: <code>$table</code></p>
            <p>Total notes: <strong>$count</strong></p>

            <hr/>

            <h3>Truncate Notes</h3>
            <p>This will <strong>delete all notes</strong> and their associated short URLs. This action cannot be undone.</p>
            <form method="post" onsubmit="return confirm('Are you sure? This will permanently delete ALL notes and their short URLs.');">
                <input type="hidden" name="nonce" value="$nonce" />
                <input type="hidden" name="notes_action" value="truncate" />
                <p><input type="submit" value="Truncate Notes Table" class="button" /></p>
            </form>
        </main>
    HTML;
}
