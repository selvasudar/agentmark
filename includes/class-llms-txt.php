<?php
namespace AgentMark;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LLMS_Txt {
	public function __construct() {
		add_action( 'wp_head', [ $this, 'inject_alternate_link' ] );
	}

	public function render() {
		$settings = new Settings();
		if ( ! $settings->is_llms_txt_enabled() ) {
			status_header( 404 );
			echo esc_html__( 'llms.txt is disabled.', 'agentmark' );
			return;
		}

		header( 'Content-Type: text/plain; charset=UTF-8' );

		echo "# " . esc_html( get_bloginfo( 'name' ) ) . "\n\n";
		echo "> " . esc_html( $settings->get_site_description() ) . "\n\n";

		$allowed_types = $settings->get_allowed_post_types();
		
		foreach ( $allowed_types as $post_type ) {
			$type_obj = get_post_type_object( $post_type );
			if ( ! $type_obj ) continue;

			$page = max( 1, get_query_var( 'agentmark_llms_page', 1 ) );
			$query = new \WP_Query( [
				'post_type' => $post_type,
				'post_status' => 'publish',
				'posts_per_page' => 100,
				'paged' => $page,
				'no_found_rows' => false,
			] );

			if ( $query->have_posts() ) {
				echo "## " . esc_html( $type_obj->label ) . "\n\n";
				echo "| " . esc_html__( 'Page Title', 'agentmark' ) . " | " . esc_html__( 'Markdown URL (.md)', 'agentmark' ) . " |\n";
				echo "| :--- | :--- |\n";

				while ( $query->have_posts() ) {
					$query->the_post();
					$title = get_the_title();
					$actual_url = untrailingslashit( get_permalink() );
					$markdown_url = $actual_url . '.md';
					
					// Check if disabled for this post (we'll implement this meta box next)
					$disable_llms = get_post_meta( get_the_ID(), '_agentmark_disable_llms', true );
					if ( $disable_llms ) continue;

					printf( "| %s | %s |\n", esc_html( $title ), esc_url( $markdown_url ) );
				}
				echo "\n";

				if ( $query->max_num_pages > 1 ) {
					echo "### " . esc_html__( 'More', 'agentmark' ) . " " . esc_html( $type_obj->label ) . "\n";
					for ( $i = 1; $i <= $query->max_num_pages; $i++ ) {
						if ( $i == $page ) continue;
						echo "- " . esc_html__( 'Page', 'agentmark' ) . " " . esc_html( $i ) . ": " . esc_url( add_query_arg( 'llms_page', $i, home_url( 'llms.txt' ) ) ) . "\n";
					}
					echo "\n";
				}

				wp_reset_postdata();
			}
		}

		echo "## " . esc_html__( 'Guidelines', 'agentmark' ) . "\n";
		echo esc_html( $settings->get_ai_guidelines() ) . "\n";
	}

	public function inject_alternate_link() {
		if ( is_singular() ) {
			$post = get_post();
			$settings = new Settings();
			$allowed_types = $settings->get_allowed_post_types();

			if ( in_array( $post->post_type, $allowed_types ) ) {
				echo '<link rel="alternate" type="text/markdown" href="' . esc_url( untrailingslashit( get_permalink() ) . '.md' ) . '">' . "\n";
			}
		}
	}
}
