<?php
namespace AgentMark;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Router {
	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'handle_md_request' ] );
	}

	public function add_rewrite_rules() {
		// Rule for llms.txt
		add_rewrite_rule( '^llms\.txt$', 'index.php?agentmark_llms=1', 'top' );

		// Rule for .md endpoints
		add_rewrite_rule( '^(.+)\.md/?$', 'index.php?agentmark_md=1&agentmark_path=$matches[1]', 'top' );
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'agentmark_llms';
		$vars[] = 'agentmark_md';
		$vars[] = 'agentmark_path';
		return $vars;
	}

	public function handle_md_request() {
		// Case 1: llms.txt
		if ( get_query_var( 'agentmark_llms' ) || $this->is_llms_txt_path() ) {
			$rate_limiter = new Rate_Limiter();
			if ( ! $rate_limiter->check_rate_limit() ) {
				status_header( 429 );
				header( 'Retry-After: 3600' );
				echo esc_html__( 'Rate limit exceeded. Please try again later.', 'agentmark' );
				exit;
			}

			$llms_txt = new LLMS_Txt();
			$llms_txt->render();
			exit;
		}

		// Case 2: Markdown Endpoint (.md) via rewrite rules
		if ( get_query_var( 'agentmark_md' ) ) {
			$path = get_query_var( 'agentmark_path' );
			$this->serve_markdown( $path );
			exit;
		}

		// Case 2b: Fallback for .md extension (if rewrite rules are not yet flushed)
		if ( $this->is_md_extension_url() ) {
			$path = $this->get_path_from_uri();
			if ( $path ) {
				$this->serve_markdown( $path );
				exit;
			}
		}

		// Case 3: Content Negotiation (Accept: text/markdown)
		if ( $this->is_markdown_requested() && is_singular() ) {
			$this->serve_markdown_for_current_post();
			exit;
		}
	}

	private function is_llms_txt_path() {
		return ( strpos( $_SERVER['REQUEST_URI'], '/llms.txt' ) !== false );
	}

	private function is_md_extension_url() {
		$uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		return ( substr( $uri, -3 ) === '.md' );
	}

	private function get_path_from_uri() {
		$uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		
		// Remove .md extension
		$path = substr( $uri, 0, -3 );

		// Remove site subdirectory if present
		$site_path = parse_url( home_url(), PHP_URL_PATH );
		if ( $site_path && $site_path !== '/' ) {
			if ( strpos( $path, $site_path ) === 0 ) {
				$path = substr( $path, strlen( $site_path ) );
			}
		}

		return trim( $path, '/' );
	}

	private function is_markdown_requested() {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		return ( strpos( $accept, 'text/markdown' ) !== false );
	}

	private function serve_markdown_for_current_post() {
		$post = get_post();
		if ( $post ) {
			$this->render_markdown( $post );
		} else {
			status_header( 404 );
			echo esc_html__( 'Post not found.', 'agentmark' );
		}
	}

	private function serve_markdown( $path ) {
		try {
			// Find the post by path
			$post_id = url_to_postid( home_url( $path ) );

			if ( ! $post_id ) {
				status_header( 404 );
				nocache_headers();
				echo esc_html__( 'Post not found.', 'agentmark' );
				return;
			}

			$post = get_post( $post_id );
			if ( ! $post ) {
				status_header( 404 );
				echo esc_html__( 'Post not found.', 'agentmark' );
				return;
			}

			$this->render_markdown( $post );
		} catch ( \Exception $e ) {
			status_header( 500 );
			echo esc_html__( 'An error occurred generating markdown.', 'agentmark' );
		}
	}

	private function render_markdown( $post ) {
		status_header( 200 );

		$rate_limiter = new Rate_Limiter();
		if ( ! $rate_limiter->check_rate_limit() ) {
			status_header( 429 );
			header( 'Retry-After: 3600' );
			echo esc_html__( 'Rate limit exceeded. Please try again later.', 'agentmark' );
			return;
		}

		// Check accessibility: Published posts are public. 
		// For non-published posts, check if the current user has permission.
		$is_public = ( $post && $post->post_status === 'publish' );
		$can_read  = current_user_can( 'read_post', $post->ID );

		if ( ! $is_public && ! $can_read ) {
			status_header( 403 );
			echo esc_html__( 'You do not have permission to view this content.', 'agentmark' );
			return;
		}

		if ( post_password_required( $post ) ) {
			status_header( 403 );
			echo esc_html__( 'This content is password protected.', 'agentmark' );
			return;
		}

		// Check if post type is enabled
		$settings = new Settings();
		$allowed_types = $settings->get_allowed_post_types();
		if ( ! in_array( $post->post_type, $allowed_types ) ) {
			status_header( 403 );
			echo esc_html__( 'Post type not allowed.', 'agentmark' );
			return;
		}

		// Check if disabled for this post
		$disabled = get_post_meta( $post->ID, '_agentmark_disable_md', true );
		if ( $disabled ) {
			status_header( 403 );
			echo esc_html__( 'Markdown endpoint disabled for this post.', 'agentmark' );
			return;
		}

		$cache_key = 'agentmark_md_' . $post->ID;
		$markdown = get_transient( $cache_key );

		if ( false === $markdown ) {
			$converter = new Markdown_Converter();
			$markdown = $converter->convert( $post );

			// Cache for 1 hour
			set_transient( $cache_key, $markdown, HOUR_IN_SECONDS );
		}

		// Log access
		$analytics = new Analytics();
		$analytics->log_access( $post->ID, 'md' );

		$word_count = str_word_count( wp_strip_all_tags( $markdown ) );

		header( 'Content-Type: text/markdown; charset=UTF-8' );
		header( 'Vary: Accept' );
		header( 'X-Markdown-Generator: AgentMark' );
		header( 'X-Markdown-Word-Count: ' . absint( $word_count ) );

		// Use wp_kses_post for late-escaping. While this is Markdown, wp_kses_post
		// ensures only safe HTML remains if any HTML was included in the Markdown.
		echo wp_kses_post( $markdown );
	}
}
