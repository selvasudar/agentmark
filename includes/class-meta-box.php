<?php
namespace AgentMark;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Meta_Box {
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );
	}

	public function add_meta_box() {
		$settings = new Settings();
		$post_types = $settings->get_allowed_post_types();

		add_meta_box(
			'agentmark_options',
			__( 'AgentMark Options', 'agentmark' ),
			[ $this, 'render_meta_box' ],
			$post_types,
			'side',
			'default'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'agentmark_meta_box', 'agentmark_meta_box_nonce' );
		$disable_md = get_post_meta( $post->ID, '_agentmark_disable_md', true );
		$disable_llms = get_post_meta( $post->ID, '_agentmark_disable_llms', true );
		?>
		<p>
			<label>
				<input type="checkbox" name="agentmark_disable_md" value="1" <?php checked( $disable_md, 1 ); ?>>
				<?php esc_html_e( 'Disable .md endpoint', 'agentmark' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="agentmark_disable_llms" value="1" <?php checked( $disable_llms, 1 ); ?>>
				<?php esc_html_e( 'Exclude from llms.txt', 'agentmark' ); ?>
			</label>
		</p>
		<?php
	}

	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['agentmark_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['agentmark_meta_box_nonce'] ) ), 'agentmark_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$disable_md = isset( $_POST['agentmark_disable_md'] ) ? 1 : 0;
		$disable_llms = isset( $_POST['agentmark_disable_llms'] ) ? 1 : 0;

		update_post_meta( $post_id, '_agentmark_disable_md', $disable_md );
		update_post_meta( $post_id, '_agentmark_disable_llms', $disable_llms );
		
		// Invalidate cache
		delete_transient( 'agentmark_md_' . $post_id );
	}
}
