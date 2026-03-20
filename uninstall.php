<?php
// This file is loaded by YOURLS during plugin deactivation.
// YOURLS_UNINSTALL_PLUGIN is defined AFTER this file is loaded (not before),
// so we cannot use it as a guard. Instead, this file should only perform
// cleanup tasks that are safe to run on deactivation.
//
// Note: We intentionally do NOT drop the notes table on deactivation,
// so that note data is preserved if the plugin is reactivated.
// To fully remove data, manually drop the table `{prefix}_notes` from the database.
