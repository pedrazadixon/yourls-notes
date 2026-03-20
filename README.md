# 📝 YOURLS Notes Plugin

Extend your YOURLS instance with **note-sharing capabilities**. All standard URL shortening keeps working as usual — this plugin simply adds the ability to create short URLs that display text or Markdown notes instead of redirecting.

> Keep shortening URLs **and** share notes, all from the same YOURLS admin panel.

---

## ✨ Features

| Feature | Description |
|---|---|
| **Note creation** | Dedicated form in the admin panel to create notes with optional custom short URL |
| **Markdown rendering** | Notes can be rendered as Markdown (via [marked.js](https://github.com/markedjs/marked)) or displayed as raw plain text |
| **Render toggle** | Per-note checkbox to enable/disable Markdown rendering |
| **Inline editing** | Edit note content, short URL keyword and render mode directly from the admin table |
| **Search integration** | Admin search also matches against note content, not just URLs and keywords |
| **Note preview** | Admin table shows a preview of the note content instead of the internal URL |
| **Stats page** | Stats page shows a note content preview instead of the internal placeholder URL, displayed as plain text |
| **Click tracking** | Clicks / visits to notes are tracked just like regular short URLs |
| **Auto-cleanup** | Deleting a short URL automatically removes the associated note |

---

## 📋 Requirements

- **YOURLS** 1.7.3 or later
- **PHP** 7.4+
- **MySQL / MariaDB** with InnoDB support

---

## 🚀 Installation

### 1. Download

Clone or download this repository into your YOURLS plugins directory:

```bash
cd /path/to/yourls/user/plugins
git clone https://github.com/pedrazadixon/yourls-notes yourls-notes
```

Or download the ZIP and extract it so the directory structure looks like:

```
yourls/
└── user/
    └── plugins/
        └── yourls-notes/
            ├── assets/
            │   ├── note-page.css
            │   ├── notes.css
            │   └── notes.js
            ├── includes/
            │   ├── admin.php
            │   ├── ajax.php
            │   ├── display.php
            │   ├── helpers.php
            │   ├── settings.php
            │   └── stats.php
            ├── plugin.php
            └── uninstall.php
```

### 2. Activate

1. Log in to your **YOURLS admin panel**.
2. Navigate to **Manage Plugins**.
3. Find **"Notes"** in the list and click **Activate**.

On activation the plugin will automatically create a `{prefix}_notes` table in your database.

### 3. Verify

After activation you should see a **📝 Create a Note** button on the admin home page, right below the standard "Shorten URL" form.

---

## 📖 Usage

### Creating a note

1. Click the **📝 Create a Note** button in the admin panel.
2. Write your note content in the textarea.
3. *(Optional)* Enter a custom keyword for the short URL.
4. Check or uncheck **Render Markdown** depending on whether you want the note displayed as formatted Markdown or raw text.
5. Click **Create Note**.

The resulting short URL (e.g. `https://sho.rt/myNote`) will display the note content instead of redirecting.

### Editing a note

1. In the admin link table, click the **Edit** button on any note row.
2. Modify the note content, the keyword, or the Markdown toggle.
3. Click **Save**.

### Markdown support

When **Render Markdown** is enabled, the note is rendered client-side using [marked.js](https://github.com/markedjs/marked). You can use standard Markdown syntax:

```markdown
# Heading

**Bold text**, *italic text*, and `inline code`.

- List item 1
- List item 2

> Blockquote

[Link](https://example.com)
```

When disabled, the note content is displayed as plain pre-formatted text.

### Settings page

Navigate to **Manage Plugins → Notes Settings** to access the settings page. From there you can:

- View the notes table name and total note count.
- **Truncate** all notes and their associated short URLs (with confirmation prompt).

---

## 🏗️ Architecture

```
yourls-notes/
├── plugin.php            # Bootstrap: constants, module loading, activation & cleanup hooks
├── uninstall.php         # Deactivation guard (data preserved by design)
├── includes/
│   ├── helpers.php       # DB helper functions (get table, check note, get content)
│   ├── admin.php         # Admin UI: form, table row badge, edit row, search filter, assets
│   ├── ajax.php          # AJAX endpoints: create note, save edited note
│   ├── display.php       # Public rendering: intercept redirect, render HTML page
│   ├── settings.php      # Settings page: table info, truncate action
│   └── stats.php         # Stats page tweaks: preview instead of internal URL
└── assets/
    ├── notes.js          # Admin JS: form toggle, AJAX create/edit
    ├── notes.css         # Admin styles: form, badges, feedback
    └── note-page.css     # Public note page styles (minimalist)
```

### Database

The plugin creates a single table `{prefix}_notes`:

| Column | Type | Description |
|---|---|---|
| `keyword` | `VARCHAR(200)` PK | Links to the YOURLS URL table keyword |
| `note_content` | `TEXT` | The note body (plain text or Markdown) |
| `render_md` | `TINYINT(1)` | `1` = render as Markdown, `0` = raw text |
| `created_at` | `DATETIME` | Timestamp of creation |

---

## 🗑️ Uninstall

The plugin **preserves note data** when deactivated, so you can safely reactivate it later without losing content.

To remove all note data you have two options:

### Option A: Settings page (recommended)

1. Go to **Manage Plugins → Notes Settings**.
2. Click **Truncate Notes Table** (this removes all notes and their short URLs).
3. **Deactivate** the plugin.

### Option B: Manual SQL

1. **Deactivate** the plugin from the YOURLS admin panel.
2. **Delete** the `yourls-notes` folder from `user/plugins/`.
3. **Drop** the notes table manually:

```sql
DROP TABLE IF EXISTS `yourls_notes`;
```

*(Replace `yourls_` with your actual YOURLS table prefix.)*

---

## 📄 License

MIT

---

## 👤 Author

**pedrazadixon** — [GitHub](https://github.com/pedrazadixon)
