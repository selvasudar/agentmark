<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AgentMark
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'agentmark_settings' );
delete_option( 'agentmark_access_logs' );

// Delete transients (using compliant approach)
global $wpdb;
/**
 * Direct database query is required here to find and delete all dynamic transients
 * created by the plugin (e.g., agentmark_md_{post_id}). WordPress does not provide
 * a native way to delete transients by prefix.
 */
$agentmark_prefix     = '_transient_agentmark_';
$agentmark_transients = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( $agentmark_prefix ) . '%'
	)
);

if ( ! empty( $agentmark_transients ) ) {
	foreach ( $agentmark_transients as $agentmark_option_name ) {
		$agentmark_transient_name = str_replace( '_transient_', '', $agentmark_option_name );
		delete_transient( $agentmark_transient_name );
	}
}

// If multisite, delete from all sites
if ( is_multisite() ) {
	$agentmark_sites = get_sites( [ 'fields' => 'ids' ] );

	foreach ( $agentmark_sites as $blog_id ) {
		switch_to_blog( $blog_id );
		delete_option( 'agentmark_settings' );
		delete_option( 'agentmark_access_logs' );
		
		// Delete site-specific transients
		$agentmark_site_prefix     = '_transient_agentmark_';
		$agentmark_site_transients = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $agentmark_site_prefix ) . '%'
			)
		);
		if ( ! empty( $agentmark_site_transients ) ) {
			foreach ( $agentmark_site_transients as $agentmark_option_name ) {
				$agentmark_transient_name = str_replace( '_transient_', '', $agentmark_option_name );
				delete_transient( $agentmark_transient_name );
			}
		}
		
		restore_current_blog();
	}
}
