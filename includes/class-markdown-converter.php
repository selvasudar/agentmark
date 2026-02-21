<?php
namespace AgentMark;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Markdown_Converter {
	public function convert( $post_object ) {
		$settings = new Settings();
		$output = '';

		if ( $settings->is_front_matter_enabled() ) {
			$output .= "---\n";
			$output .= "title: " . str_replace( '"', '\"', get_the_title( $post_object ) ) . "\n";
			$output .= "url: " . get_permalink( $post_object ) . "\n";
			$output .= "published: " . get_the_date( 'c', $post_object ) . "\n";
			$output .= "modified: " . get_the_modified_date( 'c', $post_object ) . "\n";
			$output .= "author: " . get_the_author_meta( 'display_name', $post_object->post_author ) . "\n";
			$output .= "post_type: " . $post_object->post_type . "\n";

			// Add categories
			$categories = get_the_category( $post_object->ID );
			if ( ! empty( $categories ) ) {
				$cat_names = array_map( function( $cat ) {
					return $cat->name;
				}, $categories );
				$output .= "categories: [" . implode( ', ', $cat_names ) . "]\n";
			}

			// Add tags
			$tags = get_the_tags( $post_object->ID );
			if ( ! empty( $tags ) ) {
				$tag_names = array_map( function( $tag ) {
					return $tag->name;
				}, $tags );
				$output .= "tags: [" . implode( ', ', $tag_names ) . "]\n";
			}

			// Add featured image
			if ( has_post_thumbnail( $post_object->ID ) ) {
				$output .= "featured_image: " . get_the_post_thumbnail_url( $post_object->ID, 'full' ) . "\n";
			}

			// Add excerpt
			if ( ! empty( $post_object->post_excerpt ) ) {
				$output .= "excerpt: \"" . str_replace( '"', '\"', $post_object->post_excerpt ) . "\"\n";
			}

			// Add word count
			$word_count = str_word_count( wp_strip_all_tags( $post_object->post_content ) );
			$output .= "word_count: " . $word_count . "\n";

			// Add reading time (assuming 200 words per minute)
			$reading_time = ceil( $word_count / 200 );
			$output .= "reading_time_minutes: " . $reading_time . "\n";

			$output .= "---\n\n";
		}

		$output .= "# " . get_the_title( $post_object ) . "\n\n";

		// Use the core the_content() function to ensure all filters are applied
		// This also avoids the "Non-prefixed hook name" warning from Plugin Check
		ob_start();
		global $post;
		$original_post = $post;
		$post = $post_object;
		setup_postdata( $post );
		the_content();
		$content = ob_get_clean();
		wp_reset_postdata();
		$post = $original_post;
		
		// Basic HTML to Markdown conversion
		$markdown = $this->html_to_markdown( $content );
		
		// Normalize whitespace
		$markdown = preg_replace( '/\h+$/m', '', $markdown );
		$markdown = preg_replace( '/(\r?\n){3,}/', "\n\n", $markdown );
		$markdown = trim( $markdown );

		$output .= $markdown;

		return apply_filters( 'agentmark_markdown_output', $output, $post_object );
	}

	private function html_to_markdown( $html ) {
		if ( ! class_exists( 'AgentMark\Vendor\League\HTMLToMarkdown\HtmlConverter' ) ) {
			// Fallback to basic conversion if library not available
			return $this->basic_html_to_markdown( $html );
		}

		$converter = new \AgentMark\Vendor\League\HTMLToMarkdown\HtmlConverter( [
			'strip_tags'        => true,
			'preserve_comments' => false,
		] );

		return $converter->convert( $html );
	}

	private function basic_html_to_markdown( $html ) {
		// Remove scripts and styles
		$html = preg_replace( '/<(script|style)[^>]*?>.*?<\/\\1>/si', '', $html );
		
		// Headings
		for ( $i = 1; $i <= 6; $i++ ) {
			$html = preg_replace( '/<h' . $i . '[^>]*?>(.*?)<\/h' . $i . '>/si', str_repeat( '#', $i ) . ' $1' . "\n\n", $html );
		}

		// Paragraphs
		$html = preg_replace( '/<p[^>]*?>(.*?)<\/p>/si', "$1\n\n", $html );

		// Bold/Italic
		$html = preg_replace( '/<(strong|b)[^>]*?>(.*?)<\/\\1>/si', '**$2**', $html );
		$html = preg_replace( '/<(em|i)[^>]*?>(.*?)<\/\\1>/si', '*$2*', $html );

		// Links
		$html = preg_replace( '/<a[^>]*?href=["\'](.*?)["\'][^>]*?>(.*?)<\/a>/si', '[$2]($1)', $html );

		// Images
		$html = preg_replace( '/<img[^>]*?src=["\'](.*?)["\'][^>]*?alt=["\'](.*?)["\'][^>]*?>/si', '![$2]($1)', $html );
		// Try without alt
		$html = preg_replace( '/<img[^>]*?src=["\'](.*?)["\'][^>]*?>/si', '![Image]($1)', $html );

		// Lists
		$html = preg_replace( '/<ul[^>]*?>(.*?)<\/ul>/si', "$1\n", $html );
		$html = preg_replace( '/<ol[^>]*?>(.*?)<\/ol>/si', "$1\n", $html );
		$html = preg_replace( '/<li[^>]*?>(.*?)<\/li>/si', "- $1\n", $html );

		// Strip remaining tags but keep content
		$html = wp_strip_all_tags( $html );

		// Clean up multiple newlines
		$html = preg_replace( "/\n{3,}/", "\n\n", $html );

		return trim( $html );
	}
}
