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
		// Strictly match page.md and exclude page/.md
		add_rewrite_rule( '(.+[^/])\.md$', 'index.php?agentmark_md=1&agentmark_path=$matches[1]', 'top' );
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'agentmark_llms';
		$vars[] = 'agentmark_md';
		$vars[] = 'agentmark_path';
		return $vars;
	}

	public function handle_md_request() {
		if ( get_query_var( 'agentmark_llms' ) ) {
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

		if ( get_query_var( 'agentmark_md' ) ) {
			$path = get_query_var( 'agentmark_path' );
			$this->serve_markdown( $path );
			exit;
		}
	}

	private function serve_markdown( $path ) {
		try {
			$rate_limiter = new Rate_Limiter();
			if ( ! $rate_limiter->check_rate_limit() ) {
				status_header( 429 );
				header( 'Retry-After: 3600' );
				echo esc_html__( 'Rate limit exceeded. Please try again later.', 'agentmark' );
				return;
			}

			// Find the post by path
			$post_id = url_to_postid( home_url( $path ) );

			if ( ! $post_id ) {
				status_header( 404 );
				nocache_headers();
				echo esc_html__( 'Post not found.', 'agentmark' );
				return;
			}

			// Check cache first
			$cache_key = 'agentmark_md_' . $post_id;
			$markdown = get_transient( $cache_key );

			if ( false === $markdown ) {
				$post = get_post( $post_id );

				// Check visibility
				if ( ! $post || $post->post_status !== 'publish' ) {
					status_header( 404 );
					echo esc_html__( 'Post not accessible.', 'agentmark' );
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

				$converter = new Markdown_Converter();
				$markdown = $converter->convert( $post );

				// Cache for 1 hour
				set_transient( $cache_key, $markdown, HOUR_IN_SECONDS );
			}

			// Log access
			$analytics = new Analytics();
			$analytics->log_access( $post_id, 'md' );

			header( 'Content-Type: text/markdown; charset=UTF-8' );
			// Use wp_kses_post for late-escaping. While this is Markdown, wp_kses_post
			// ensures only safe HTML remains if any HTML was included in the Markdown.
			echo wp_kses_post( $markdown );
		} catch ( \Exception $e ) {
			status_header( 500 );
			echo esc_html__( 'An error occurred generating markdown.', 'agentmark' );
		}
	}
}
