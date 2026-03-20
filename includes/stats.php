<?php
/**
 * Notes Plugin - Stats page tweaks and filters
 */

// No direct call
if (!defined('YOURLS_ABSPATH'))
    die();

// ─── Prevent "URL is a short URL" error for internal note URLs ───
yourls_add_filter('is_shorturl', 'notes_allow_internal_url');
function notes_allow_internal_url($is_short, $url)
{
    if (strpos($url, NOTES_INTERNAL_PREFIX) === 0) {
        return false;
    }
    return $is_short;
}

// ─── Stats page: replace internal URL with note preview ───
yourls_add_filter('get_keyword_info', 'notes_replace_longurl_with_preview');
function notes_replace_longurl_with_preview($return, $keyword, $field, $notfound)
{
    // Only modify the 'url' field
    if ($field !== 'url') {
        return $return;
    }

    // Check if the URL is an internal note URL
    if (strpos($return, NOTES_INTERNAL_PREFIX) !== 0) {
        return $return;
    }

    // Replace with a note content preview (120 chars)
    $content = notes_get_content($keyword);
    if ($content !== false) {
        $return = mb_strimwidth(str_replace("\n", ' ', $content), 0, 120, '...');
    }

    return $return;
}

// ─── Stats page: make the "Long URL" plain text (not a link) for notes ───
yourls_add_action('pre_yourls_infos', 'notes_stats_page_tweaks');
function notes_stats_page_tweaks($args)
{
    $keyword = $args[0];
    if (!notes_is_note($keyword)) {
        return;
    }

    // Inject a script that runs after the page renders
    $js_script = <<<JS
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var el = document.getElementById("longurl");
                if (!el) return;
                var link = el.querySelector("a");
                if (!link) return;
                var span = document.createElement("span");
                span.textContent = link.textContent;
                link.replaceWith(span);
                // Also hide the favicon img (no real URL to fetch icon from)
                var img = el.querySelector("img");
                if (img) img.style.display = "none";
                // Change label
                var label = el.querySelector(".label");
                if (label) label.textContent = "Note:";
            });
        </script>
    JS;

    yourls_add_action('html_footer', function () use ($js_script) {
        echo $js_script;
    });
}
