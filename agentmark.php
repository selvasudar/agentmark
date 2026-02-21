<?php
/**
 * Plugin Name: AgentMark
 * Description: AI-Ready Markdown Endpoints & llms.txt for WordPress.
 * Version: 1.0.0
 * Author: Selvakumar Duraipandian
 * Author URI: https://github.com/selvasudar
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: agentmark
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'AGENTMARK_VERSION', '1.0.0' );
define( 'AGENTMARK_PATH', plugin_dir_path( __FILE__ ) );
define( 'AGENTMARK_URL', plugin_dir_url( __FILE__ ) );

// Autoload classes (basic for includes, composer for vendor)
require_once AGENTMARK_PATH . 'vendor/autoload.php';

spl_autoload_register( function ( $class ) {
	$prefix = 'AgentMark\\';
	$base_dir = AGENTMARK_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );

	// Handle AgentMark\Vendor separately to avoid the class- prefix
	if ( strpos( $relative_class, 'Vendor\\' ) === 0 ) {
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
	} else {
		$file = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
	}

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

// Initialize plugin
add_action( 'plugins_loaded', function() {
	new AgentMark\Router();
	new AgentMark\Settings();
	new AgentMark\LLMS_Txt();
	new AgentMark\Meta_Box();
} );

// Cache invalidation on post update
add_action( 'save_post', function( $post_id ) {
	delete_transient( 'agentmark_md_' . $post_id );
} );

// Activation/Deactivation hooks
register_activation_hook( __FILE__, function() {
	new AgentMark\Router();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function() {
	flush_rewrite_rules();
} );
