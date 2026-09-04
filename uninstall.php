<?php
/**
 * ISKO Chat Support — uninstall cleanup.
 * Removes only plugin-owned options. The "ISKO Chat" page is left in place
 * (deleting content is never automatic); you may trash it manually.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'isko_chat_slug' );
delete_option( 'isko_chat_page_id' );
