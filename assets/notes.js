/**
 * Notes Plugin - Admin JavaScript
 */

/**
 * Toggle the Notes form visibility
 */
function notes_toggle_form() {
    var wrap = document.getElementById('notes-form-wrap');
    var btn = document.getElementById('notes-toggle-btn');

    if (wrap.style.display === 'none') {
        wrap.style.display = 'block';
        btn.classList.add('active');
        document.getElementById('note-content').focus();
    } else {
        wrap.style.display = 'none';
        btn.classList.remove('active');
    }
}

/**
 * Create a new note via AJAX
 */
function notes_add() {
    var noteContent = document.getElementById('note-content').value.trim();
    var keyword = document.getElementById('note-keyword').value.trim();
    var nonce = document.getElementById('note-nonce').value;
    var feedback = document.getElementById('note-feedback');

    if (!noteContent) {
        notes_feedback('Please enter note content.', 'fail');
        return;
    }

    // Disable button while processing
    var btn = document.getElementById('note-submit');
    btn.disabled = true;
    btn.value = 'Creating...';

    var renderMd = document.getElementById('note-render-md').checked ? 1 : 0;

    var data = {
        action: 'notes_add',
        note_content: noteContent,
        keyword: keyword,
        render_md: renderMd,
        nonce: nonce
    };

    $.ajax({
        type: 'POST',
        url: ajaxurl,
        data: data,
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                var shorturl = response.shorturl;
                notes_feedback(
                    'Note created! Short URL: <a href="' + shorturl + '" target="_blank">' + shorturl + '</a>' +
                    ' <input type="text" class="text" size="30" value="' + shorturl + '" onclick="this.select();" readonly />',
                    'success'
                );
                // Clear the form
                document.getElementById('note-content').value = '';
                document.getElementById('note-keyword').value = '';
                document.getElementById('note-render-md').checked = true;

                // Add the new row to the table if the html is available in the response
                if (response.html) {
                    $('#main_table tbody').prepend(response.html);
                    // Increment counters
                    $('.increment').each(function () {
                        var val = parseInt($(this).text().replace(/,/g, '')) || 0;
                        $(this).text((val + 1).toLocaleString());
                    });
                }
            } else {
                notes_feedback(response.message || 'Error creating note.', 'fail');
            }
        },
        error: function () {
            notes_feedback('Network error. Please try again.', 'fail');
        },
        complete: function () {
            btn.disabled = false;
            btn.value = 'Create Note';
        }
    });
}

/**
 * Display feedback message
 */
function notes_feedback(message, status) {
    var el = document.getElementById('note-feedback');
    el.innerHTML = '<p class="note-feedback-' + status + '">' + message + '</p>';
    el.style.display = 'block';

    if (status === 'success') {
        setTimeout(function () {
            el.style.display = 'none';
        }, 15000);
    }
}

/**
 * Save edited note via AJAX
 */
function notes_edit_save(id) {
    add_loading("#edit-close-" + id);

    var noteContent = $('#edit-note-content-' + id).val();
    var newkeyword = $('#edit-keyword-' + id).val();
    var keyword = $('#old_keyword_' + id).val();
    var nonce = $('#nonce_' + id).val();
    var renderMd = $('#edit-render-md-' + id).is(':checked') ? 1 : 0;

    $.getJSON(
        ajaxurl,
        {
            action: 'notes_edit_save',
            note_content: noteContent,
            keyword: keyword,
            newkeyword: newkeyword,
            render_md: renderMd,
            nonce: nonce,
            id: id
        },
        function (data) {
            if (data.status == 'success') {
                // Update the URL cell to match the PHP template style
                var preview = data.note_preview || '';
                var shorturl = data.url.shorturl;
                var keyword = data.url.keyword;
                $('#url-' + id).html(
                    '<a href="' + shorturl + '" title="' + shorturl + '">Note ' + $('<span>').text(keyword).html() + '</a><br/>' +
                    '<small class="notes-badge-note">' + $('<span>').text(preview).html() + '</small>'
                );
                // Remove edit row
                $('#edit-' + id).fadeOut(200, function () {
                    $(this).remove();
                    $('#main_table tbody').trigger("update");
                });
                $('#keyword_' + id).val(newkeyword);
                end_disable('#actions-' + id + ' .button');
            }
            feedback(data.message, data.status);
            end_loading("#edit-close-" + id);
            end_disable("#edit-close-" + id);
        }
    );
}
