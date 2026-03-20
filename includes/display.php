<?php
/**
 * Notes Plugin - Public note rendering (redirect interception + HTML page)
 */

// No direct call
if (!defined('YOURLS_ABSPATH'))
    die();

// ─── Intercept redirect: display note instead of redirecting ───
yourls_add_action('redirect_shorturl', 'notes_intercept_redirect', 1);
function notes_intercept_redirect($args)
{
    $url = $args[0];
    $keyword = $args[1];

    // Check if this is a note (either by internal URL or by DB lookup)
    if (strpos($url, NOTES_INTERNAL_PREFIX) !== 0 && !notes_is_note($keyword)) {
        return; // Not a note, let YOURLS redirect normally
    }

    $note = notes_get_note($keyword);
    if (!$note) {
        return; // No note found, fall through
    }

    // The redirect_shorturl action fires BEFORE yourls_update_clicks() and
    // yourls_log_redirect() in core. Since we die() here, we must do it manually.
    yourls_update_clicks($keyword);
    yourls_log_redirect($keyword);

    // Render the note page
    notes_render_page($keyword, $note->note_content, (int) $note->render_md);
    die(); // Stop YOURLS from redirecting
}

// ─── Render the public note page ───
function notes_render_page($keyword, $content, $render_md = 1)
{
    $site_url = yourls_get_yourls_site();
    $css_url = yourls_plugin_url(NOTES_PLUGIN_DIR) . '/assets/display.css?v=1.0';

    // Encode raw content as JSON for safe JS embedding (handles quotes, newlines, etc.)
    $json_content = json_encode($content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Note - <?php echo yourls_esc_html($site_url); ?></title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="stylesheet" href="<?php echo $css_url; ?>" />
    </head>
    <body>
        <div class="note">
            <div class="note-content" id="note-md"></div>
        </div>

        <?php if ($render_md): ?>
            <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
            <script>
                var renderer = new marked.Renderer();
                renderer.heading = function ({ text, depth }) {
                    var slug = text.toLowerCase()
                        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^\w\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');
                    return '<h' + depth + ' id="' + slug + '">' + text + '</h' + depth + '>';
                };
                marked.setOptions({ renderer: renderer });

                var raw = <?php echo $json_content; ?>;
                document.getElementById('note-md').innerHTML = marked.parse(raw);
            </script>
        <?php else: ?>
            <script>
                var raw = <?php echo $json_content; ?>;
                var el = document.getElementById('note-md');
                el.classList.add('note-raw');
                el.textContent = raw;
            </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
